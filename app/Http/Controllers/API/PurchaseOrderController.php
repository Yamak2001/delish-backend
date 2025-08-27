<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_purchase_orders' => PurchaseOrder::count(),
                'draft_orders' => PurchaseOrder::where('status', 'draft')->count(),
                'sent_orders' => PurchaseOrder::where('status', 'sent')->count(),
                'confirmed_orders' => PurchaseOrder::where('status', 'confirmed')->count(),
                'received_orders' => PurchaseOrder::where('status', 'received')->count(),
                'total_po_value' => PurchaseOrder::sum('total_amount'),
                'pending_approvals' => PurchaseOrder::where('status', 'draft')->whereNull('approved_by')->count(),
                'overdue_deliveries' => PurchaseOrder::where('status', 'confirmed')
                    ->where('expected_delivery_date', '<', now())
                    ->count(),
            ];

            $recentOrders = PurchaseOrder::with(['supplier:id,supplier_name', 'createdBy:id,name'])
                ->latest()
                ->take(5)
                ->get();

            $supplierPerformance = DB::table('purchase_orders')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->select('suppliers.supplier_name', 
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('AVG(CASE WHEN purchase_orders.status = "received" THEN 1 ELSE 0 END) * 100 as fulfillment_rate'),
                    DB::raw('SUM(total_amount) as total_value'))
                ->groupBy('suppliers.id', 'suppliers.supplier_name')
                ->orderByDesc('total_value')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_orders' => $recentOrders,
                    'supplier_performance' => $supplierPerformance,
                ],
                'message' => 'Purchase orders dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = PurchaseOrder::with(['supplier:id,supplier_name', 'createdBy:id,name', 'approvedBy:id,name']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by supplier
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('order_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('order_date', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function($sq) use ($search) {
                          $sq->where('supplier_name', 'like', "%{$search}%");
                      });
                });
            }

            $purchaseOrders = $query->orderBy('order_date', 'desc')
                                  ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $purchaseOrders,
                'message' => 'Purchase orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve purchase orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:suppliers,id',
                'expected_delivery_date' => 'required|date|after:today',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
                'items.*.quantity' => 'required|numeric|min:0.001',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Generate PO number
            $poNumber = 'PO-' . str_pad(PurchaseOrder::count() + 1, 6, '0', STR_PAD_LEFT);

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $request->supplier_id,
                'order_date' => now(),
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => 'draft',
                'currency' => 'JOD',
                'payment_terms' => 'Net 30',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;

            // Create purchase order items
            foreach ($request->items as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                $subtotal += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $lineTotal,
                    'notes' => $itemData['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Calculate totals
            $taxAmount = $subtotal * 0.16; // 16% VAT
            $totalAmount = $subtotal + $taxAmount;

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $purchaseOrder->load(['supplier', 'items.inventoryItem']),
                'message' => 'Purchase order created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder->load([
                'supplier',
                'items.inventoryItem:id,name,unit_of_measure',
                'createdBy:id,name',
                'approvedBy:id,name',
            ]);

            return response()->json([
                'success' => true,
                'data' => $purchaseOrder,
                'message' => 'Purchase order details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve purchase order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,sent,confirmed,received,cancelled',
                'notes' => 'nullable|string',
                'actual_delivery_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = ['status' => $request->status];

            // Handle status-specific updates
            switch ($request->status) {
                case 'sent':
                    if (!$purchaseOrder->approved_by) {
                        $updateData['approved_by'] = auth()->id();
                        $updateData['approved_at'] = now();
                    }
                    break;
                case 'received':
                    $updateData['actual_delivery_date'] = $request->actual_delivery_date ?? now();
                    break;
            }

            if ($request->notes) {
                $updateData['notes'] = $request->notes;
            }

            $purchaseOrder->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $purchaseOrder->fresh(),
                'message' => 'Purchase order status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}