<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryRoute;
use App\Models\DeliveryTracking;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_deliveries_today' => Delivery::whereDate('created_at', today())->count(),
                'pending_deliveries' => Delivery::where('status', 'pending')->count(),
                'in_transit_deliveries' => Delivery::where('status', 'in_transit')->count(),
                'delivered_today' => Delivery::where('status', 'delivered')->whereDate('actual_delivery_time', today())->count(),
                'failed_deliveries_today' => Delivery::where('status', 'failed')->whereDate('updated_at', today())->count(),
                'average_delivery_time' => Delivery::where('status', 'delivered')->whereNotNull('actual_duration_minutes')->avg('actual_duration_minutes'),
                'total_delivery_revenue_today' => Delivery::where('status', 'delivered')->whereDate('actual_delivery_time', today())->sum('total_delivery_cost'),
                'active_drivers' => Delivery::whereIn('status', ['assigned', 'picked_up', 'in_transit'])->distinct('assigned_driver_id')->count('assigned_driver_id'),
            ];

            $urgentDeliveries = Delivery::with(['order', 'assignedDriver:id,name'])
                ->where('priority', 'urgent')
                ->whereIn('status', ['pending', 'assigned', 'picked_up', 'in_transit'])
                ->orderBy('scheduled_delivery_time')
                ->limit(10)
                ->get();

            $overdueDeliveries = Delivery::with(['order', 'assignedDriver:id,name'])
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->where('scheduled_delivery_time', '<', now())
                ->orderBy('scheduled_delivery_time')
                ->limit(10)
                ->get();

            $driverPerformance = Delivery::selectRaw('assigned_driver_id, users.name as driver_name, 
                    COUNT(*) as total_deliveries,
                    AVG(actual_duration_minutes) as avg_delivery_time,
                    AVG(customer_rating) as avg_rating,
                    COUNT(CASE WHEN deliveries.status = "delivered" THEN 1 END) as successful_deliveries')
                ->leftJoin('users', 'deliveries.assigned_driver_id', '=', 'users.id')
                ->whereNotNull('assigned_driver_id')
                ->whereDate('deliveries.created_at', '>=', now()->subDays(7))
                ->groupBy('assigned_driver_id', 'users.name')
                ->orderByDesc('successful_deliveries')
                ->limit(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => $stats,
                    'urgent_deliveries' => $urgentDeliveries,
                    'overdue_deliveries' => $overdueDeliveries,
                    'driver_performance' => $driverPerformance,
                ],
                'message' => 'Delivery dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Delivery::with(['order', 'deliveryRoute', 'assignedDriver:id,name', 'createdBy:id,name']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('driver_id')) {
                $query->where('assigned_driver_id', $request->driver_id);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('delivery_area')) {
                $query->where('delivery_area', 'like', '%' . $request->delivery_area . '%');
            }

            if ($request->has('date')) {
                $query->whereDate('scheduled_delivery_time', $request->date);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('delivery_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%")
                      ->orWhere('delivery_address', 'like', "%{$search}%");
                });
            }

            $deliveries = $query->orderBy('scheduled_delivery_time', 'desc')
                               ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $deliveries,
                'message' => 'Deliveries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve deliveries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'delivery_type' => 'required|in:standard,express,scheduled,pickup',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email|max:255',
                'delivery_address' => 'required|string',
                'delivery_area' => 'nullable|string|max:100',
                'delivery_city' => 'nullable|string|max:100',
                'delivery_instructions' => 'nullable|string',
                'scheduled_delivery_time' => 'nullable|date',
                'delivery_fee' => 'nullable|numeric|min:0',
                'special_requirements' => 'nullable|array',
                'assigned_driver_id' => 'nullable|exists:users,id',
                'delivery_route_id' => 'nullable|exists:delivery_routes,id',
            ]);

            $validated['created_by'] = auth()->id();
            $validated['status'] = 'pending';
            
            // Auto-assign route if not provided
            if (!isset($validated['delivery_route_id']) && isset($validated['delivery_area'])) {
                $route = DeliveryRoute::whereJsonContains('coverage_areas', $validated['delivery_area'])
                    ->where('status', 'active')
                    ->first();
                if ($route) {
                    $validated['delivery_route_id'] = $route->id;
                    $validated['delivery_fee'] = $validated['delivery_fee'] ?? $route->delivery_cost_per_order;
                }
            }

            $delivery = Delivery::create($validated);

            // Create initial tracking record
            DeliveryTracking::create([
                'delivery_id' => $delivery->id,
                'status' => 'pending',
                'status_description' => 'Delivery created and pending assignment',
                'timestamp' => now(),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $delivery->load(['order', 'deliveryRoute', 'assignedDriver:id,name']),
                'message' => 'Delivery created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create delivery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Delivery $delivery): JsonResponse
    {
        try {
            $delivery->load([
                'order',
                'deliveryRoute',
                'assignedDriver:id,name,phone_number',
                'createdBy:id,name',
                'trackingHistory' => function($query) {
                    $query->with('updatedBy:id,name')->orderBy('timestamp', 'desc');
                }
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $delivery,
                'message' => 'Delivery details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve delivery details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignDriver(Request $request, Delivery $delivery): JsonResponse
    {
        try {
            $validated = $request->validate([
                'driver_id' => 'required|exists:users,id',
                'estimated_pickup_time' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            $delivery->update([
                'assigned_driver_id' => $validated['driver_id'],
                'status' => 'assigned',
                'estimated_pickup_time' => $validated['estimated_pickup_time'] ?? now()->addMinutes(30),
            ]);

            // Create tracking record
            DeliveryTracking::create([
                'delivery_id' => $delivery->id,
                'status' => 'assigned',
                'status_description' => 'Driver assigned to delivery',
                'timestamp' => now(),
                'notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $delivery->fresh(['assignedDriver:id,name']),
                'message' => 'Driver assigned successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign driver',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, Delivery $delivery): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,assigned,picked_up,in_transit,delivered,failed,returned,cancelled',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'location_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'proof_of_delivery' => 'nullable|array',
                'customer_rating' => 'nullable|numeric|min:1|max:5',
                'customer_feedback' => 'nullable|string',
                'failed_reason' => 'nullable|string',
            ]);

            // Update delivery status and relevant timestamps
            $updateData = ['status' => $validated['status']];
            
            switch ($validated['status']) {
                case 'picked_up':
                    $updateData['actual_pickup_time'] = now();
                    break;
                case 'delivered':
                    $updateData['actual_delivery_time'] = now();
                    if ($delivery->actual_pickup_time) {
                        $updateData['actual_duration_minutes'] = now()->diffInMinutes($delivery->actual_pickup_time);
                    }
                    if (isset($validated['customer_rating'])) {
                        $updateData['customer_rating'] = $validated['customer_rating'];
                    }
                    if (isset($validated['customer_feedback'])) {
                        $updateData['customer_feedback'] = $validated['customer_feedback'];
                    }
                    if (isset($validated['proof_of_delivery'])) {
                        $updateData['proof_of_delivery'] = $validated['proof_of_delivery'];
                    }
                    break;
                case 'failed':
                    if (isset($validated['failed_reason'])) {
                        $updateData['failed_reason'] = $validated['failed_reason'];
                    }
                    break;
            }

            $delivery->update($updateData);

            // Create tracking record
            DeliveryTracking::create([
                'delivery_id' => $delivery->id,
                'status' => $validated['status'],
                'status_description' => $this->getStatusDescription($validated['status']),
                'timestamp' => now(),
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'location_name' => $validated['location_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'additional_data' => $validated['proof_of_delivery'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $delivery->fresh(),
                'message' => 'Delivery status updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update delivery status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackDelivery(Delivery $delivery): JsonResponse
    {
        try {
            $tracking = DeliveryTracking::where('delivery_id', $delivery->id)
                ->with('updatedBy:id,name')
                ->orderBy('timestamp', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'delivery' => $delivery->load(['assignedDriver:id,name']),
                    'tracking_history' => $tracking,
                    'current_location' => $tracking->first(),
                ],
                'message' => 'Delivery tracking retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve tracking information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function optimizeRoutes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'delivery_ids' => 'required|array',
                'delivery_ids.*' => 'exists:deliveries,id',
                'driver_id' => 'nullable|exists:users,id',
            ]);

            $deliveries = Delivery::whereIn('id', $validated['delivery_ids'])
                ->where('status', 'pending')
                ->get();

            // Simple optimization: group by area and sort by priority
            $optimized = $deliveries->groupBy('delivery_area')
                ->map(function ($areaDeliveries) {
                    return $areaDeliveries->sortByDesc(function ($delivery) {
                        $priorities = ['low' => 1, 'normal' => 2, 'high' => 3, 'urgent' => 4];
                        return $priorities[$delivery->priority] ?? 2;
                    });
                })
                ->flatten();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'optimized_order' => $optimized->values(),
                    'total_deliveries' => $optimized->count(),
                    'estimated_total_time' => $optimized->sum('estimated_duration_minutes'),
                ],
                'message' => 'Route optimization completed successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to optimize routes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deliveryPerformanceReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'driver_id' => 'nullable|exists:users,id',
            ]);

            $query = Delivery::query();
            
            if (isset($validated['start_date'])) {
                $query->whereDate('created_at', '>=', $validated['start_date']);
            }
            
            if (isset($validated['end_date'])) {
                $query->whereDate('created_at', '<=', $validated['end_date']);
            }
            
            if (isset($validated['driver_id'])) {
                $query->where('assigned_driver_id', $validated['driver_id']);
            }

            $stats = [
                'total_deliveries' => $query->count(),
                'successful_deliveries' => (clone $query)->where('status', 'delivered')->count(),
                'failed_deliveries' => (clone $query)->where('status', 'failed')->count(),
                'average_delivery_time' => (clone $query)->where('status', 'delivered')->avg('actual_duration_minutes'),
                'average_customer_rating' => (clone $query)->where('status', 'delivered')->whereNotNull('customer_rating')->avg('customer_rating'),
                'total_delivery_revenue' => (clone $query)->where('status', 'delivered')->sum('total_delivery_cost'),
                'on_time_delivery_rate' => 0,
            ];

            // Calculate on-time delivery rate
            $totalWithScheduled = (clone $query)->whereNotNull('scheduled_delivery_time')->count();
            if ($totalWithScheduled > 0) {
                $onTime = (clone $query)
                    ->where('status', 'delivered')
                    ->whereRaw('actual_delivery_time <= scheduled_delivery_time')
                    ->count();
                $stats['on_time_delivery_rate'] = ($onTime / $totalWithScheduled) * 100;
            }

            return response()->json([
                'status' => 'success',
                'data' => $stats,
                'message' => 'Performance report generated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate performance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getStatusDescription(string $status): string
    {
        return match($status) {
            'pending' => 'Delivery is pending assignment',
            'assigned' => 'Driver has been assigned',
            'picked_up' => 'Package picked up from kitchen',
            'in_transit' => 'Package is on the way to customer',
            'delivered' => 'Package successfully delivered',
            'failed' => 'Delivery attempt failed',
            'returned' => 'Package returned to kitchen',
            'cancelled' => 'Delivery was cancelled',
            default => 'Status updated',
        };
    }
}