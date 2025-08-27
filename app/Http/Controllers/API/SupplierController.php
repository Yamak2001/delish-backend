<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Supplier::with(['createdBy:id,name']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('type')) {
                $query->where('supplier_type', $request->type);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('supplier_name', 'like', "%{$search}%")
                      ->orWhere('supplier_code', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $suppliers = $query->orderBy('supplier_name')
                              ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $suppliers,
                'message' => 'Suppliers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve suppliers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'supplier_name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'tax_number' => 'nullable|string|max:50',
                'supplier_type' => 'required|in:ingredient,packaging,equipment,service,other',
                'status' => 'nullable|in:active,inactive,suspended',
                'payment_terms' => 'nullable|in:cod,net_15,net_30,net_45,net_60,prepaid',
                'credit_limit' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'lead_time_days' => 'nullable|integer|min:0|max:365',
                'rating' => 'nullable|numeric|min:1|max:5',
                'notes' => 'nullable|string',
                'contact_info' => 'nullable|array',
            ]);

            $validated['created_by'] = auth()->id();

            $supplier = Supplier::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $supplier->load('createdBy:id,name'),
                'message' => 'Supplier created successfully'
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
                'message' => 'Failed to create supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->load([
                'createdBy:id,name',
                'purchaseOrders' => function($query) {
                    $query->with(['items:id,purchase_order_id,quantity_ordered,quantity_received'])
                          ->orderBy('po_date', 'desc')
                          ->limit(10);
                },
                'supplierInvoices' => function($query) {
                    $query->orderBy('invoice_date', 'desc')
                          ->limit(10);
                }
            ]);

            $supplier->performance_stats = $supplier->getPerformanceStats();

            return response()->json([
                'status' => 'success',
                'data' => $supplier,
                'message' => 'Supplier details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve supplier details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'supplier_name' => 'sometimes|required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'tax_number' => 'nullable|string|max:50',
                'supplier_type' => 'sometimes|required|in:ingredient,packaging,equipment,service,other',
                'status' => 'nullable|in:active,inactive,suspended',
                'payment_terms' => 'nullable|in:cod,net_15,net_30,net_45,net_60,prepaid',
                'credit_limit' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'lead_time_days' => 'nullable|integer|min:0|max:365',
                'rating' => 'nullable|numeric|min:1|max:5',
                'notes' => 'nullable|string',
                'contact_info' => 'nullable|array',
            ]);

            $supplier->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $supplier->fresh(['createdBy:id,name']),
                'message' => 'Supplier updated successfully'
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
                'message' => 'Failed to update supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        try {
            if ($supplier->purchaseOrders()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete supplier with existing purchase orders'
                ], 400);
            }

            $supplier->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_suppliers' => Supplier::count(),
                'active_suppliers' => Supplier::active()->count(),
                'suspended_suppliers' => Supplier::suspended()->count(),
                'total_outstanding_balance' => Supplier::sum('current_balance'),
                'suppliers_over_credit_limit' => Supplier::whereRaw('current_balance >= credit_limit')->count(),
                'high_rated_suppliers' => Supplier::where('rating', '>=', 4.0)->count(),
                'recent_suppliers' => Supplier::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            $topSuppliers = Supplier::active()
                ->withCount('purchaseOrders')
                ->withSum('purchaseOrders as total_po_value', 'total_amount')
                ->orderByDesc('purchase_orders_count')
                ->limit(10)
                ->get();

            $recentActivity = PurchaseOrder::with(['supplier:id,supplier_name', 'createdBy:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $overduePayables = SupplierInvoice::with('supplier:id,supplier_name')
                ->where('due_date', '<', now()->toDateString())
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')
                ->limit(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => $stats,
                    'top_suppliers' => $topSuppliers,
                    'recent_activity' => $recentActivity,
                    'overdue_payables' => $overduePayables,
                ],
                'message' => 'Supplier dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function performance(Supplier $supplier): JsonResponse
    {
        try {
            $stats = $supplier->getPerformanceStats();
            
            $recentOrders = $supplier->purchaseOrders()
                ->with(['items', 'goodsReceipts'])
                ->orderBy('po_date', 'desc')
                ->limit(20)
                ->get();

            $monthlyOrdersData = PurchaseOrder::where('supplier_id', $supplier->id)
                ->selectRaw('DATE_FORMAT(po_date, "%Y-%m") as month, COUNT(*) as count, SUM(total_amount) as total')
                ->where('po_date', '>=', now()->subYear())
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'performance_stats' => $stats,
                    'recent_orders' => $recentOrders,
                    'monthly_trends' => $monthlyOrdersData,
                ],
                'message' => 'Supplier performance data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateRating(Request $request, Supplier $supplier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rating' => 'required|numeric|min:1|max:5',
                'notes' => 'nullable|string',
            ]);

            $supplier->updateRating($validated['rating']);
            
            if (isset($validated['notes'])) {
                $supplier->update([
                    'notes' => $supplier->notes . "\n\nRating updated to {$validated['rating']}: " . $validated['notes']
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $supplier->fresh(),
                'message' => 'Supplier rating updated successfully'
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
                'message' => 'Failed to update rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function suspend(Request $request, Supplier $supplier): JsonResponse
    {
        try {
            $reason = $request->input('reason', 'No reason provided');
            
            $supplier->update([
                'status' => 'suspended',
                'notes' => $supplier->notes . "\n\nSuspended: " . $reason . " (Date: " . now()->toDateString() . ")"
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $supplier->fresh(),
                'message' => 'Supplier suspended successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to suspend supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activate(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->update([
                'status' => 'active',
                'notes' => $supplier->notes . "\n\nReactivated (Date: " . now()->toDateString() . ")"
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $supplier->fresh(),
                'message' => 'Supplier activated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}