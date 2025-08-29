<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\MerchantPricing;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    private JobTicketService $jobTicketService;
    private PricingService $pricingService;
    private WasteManagementService $wasteManagementService;

    public function __construct(
        JobTicketService $jobTicketService,
        PricingService $pricingService,
        WasteManagementService $wasteManagementService
    ) {
        $this->jobTicketService = $jobTicketService;
        $this->pricingService = $pricingService;
        $this->wasteManagementService = $wasteManagementService;
    }

    /**
     * Process WhatsApp order with business logic validation
     */
    public function processWhatsAppOrder(Merchant $merchant, array $orderData, string $whatsappMessageId): array
    {
        return DB::transaction(function () use ($merchant, $orderData, $whatsappMessageId) {
            
            // 1. Validate merchant credit limit
            $creditCheck = $this->validateMerchantCredit($merchant);
            if (!$creditCheck['valid']) {
                return ['success' => false, 'message' => $creditCheck['message']];
            }

            // 2. Calculate order total with merchant-specific pricing
            $pricingResult = $this->pricingService->calculateOrderTotal($merchant, $orderData['items']);
            if (!$pricingResult['success']) {
                return ['success' => false, 'message' => $pricingResult['message']];
            }

            // 3. Check inventory availability
            $inventoryCheck = $this->checkInventoryAvailability($orderData['items']);
            if (!$inventoryCheck['available']) {
                return ['success' => false, 'message' => $inventoryCheck['message']];
            }

            // 4. Process waste management logic (FIFO reorder detection)
            $wasteUpdate = $this->wasteManagementService->processReorder($merchant, $orderData['items']);

            // 5. Create the order
            $order = $this->createOrder($merchant, $orderData, $pricingResult, $whatsappMessageId);

            // 6. Auto-create job ticket with workflow
            $jobTicket = $this->jobTicketService->createFromOrder($order);

            // 7. Update merchant product tracking
            $this->updateProductTracking($merchant, $orderData['items'], $order->requested_delivery_date);

            Log::info("Order processed successfully", [
                'order_id' => $order->id,
                'merchant_id' => $merchant->id,
                'job_ticket_number' => $jobTicket->job_ticket_number,
                'total_amount' => $order->total_amount
            ]);

            return [
                'success' => true,
                'order' => $order->load('jobTicket'),
                'job_ticket' => $jobTicket,
                'waste_updated' => $wasteUpdate['updated_items'] ?? []
            ];
        });
    }

    /**
     * Validate merchant credit limit
     */
    private function validateMerchantCredit(Merchant $merchant): array
    {
        $outstandingAmount = $merchant->invoices()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('total_amount');
            
        $pendingOrdersAmount = $merchant->orders()
            ->where('order_status', 'pending')
            ->sum('total_amount');
            
        $totalOutstanding = $outstandingAmount + $pendingOrdersAmount;
        
        if ($totalOutstanding >= $merchant->credit_limit) {
            return [
                'valid' => false,
                'message' => "Credit limit exceeded. Outstanding: $" . number_format($totalOutstanding, 2) . 
                           ", Limit: $" . number_format($merchant->credit_limit, 2) . 
                           ". Please clear outstanding invoices before placing new orders."
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Check inventory availability for production
     */
    private function checkInventoryAvailability(array $orderItems): array
    {
        $unavailableItems = [];
        
        foreach ($orderItems as $item) {
            $recipe = Recipe::find($item['recipe_id']);
            if (!$recipe) continue;
            
            // Check if we have enough ingredients for this quantity
            foreach ($recipe->recipeIngredients as $ingredient) {
                $requiredQuantity = $ingredient->quantity_required * $item['quantity'];
                
                if ($ingredient->inventoryItem->current_quantity < $requiredQuantity) {
                    $unavailableItems[] = [
                        'recipe' => $recipe->recipe_name,
                        'ingredient' => $ingredient->inventoryItem->item_name,
                        'required' => $requiredQuantity,
                        'available' => $ingredient->inventoryItem->current_quantity
                    ];
                }
            }
        }
        
        if (!empty($unavailableItems)) {
            $message = "Insufficient ingredients:\n";
            foreach ($unavailableItems as $item) {
                $message .= "• {$item['recipe']} needs {$item['required']} {$item['ingredient']}, only {$item['available']} available\n";
            }
            return ['available' => false, 'message' => $message];
        }
        
        return ['available' => true];
    }

    /**
     * Create order record
     */
    private function createOrder(
        Merchant $merchant, 
        array $orderData, 
        array $pricingResult, 
        string $whatsappMessageId
    ): Order {
        
        // Determine workflow based on order characteristics
        $workflow = $this->selectWorkflow($orderData, $pricingResult);
        
        return Order::create([
            'merchant_id' => $merchant->id,
            'whatsapp_order_id' => $whatsappMessageId,
            'order_items' => $orderData['items'],
            'total_amount' => $pricingResult['total'],
            'order_date' => now(),
            'requested_delivery_date' => $orderData['delivery_date'] ?: now()->addDay(),
            'order_status' => 'confirmed', // Auto-confirm WhatsApp orders
            'special_notes' => $orderData['special_notes'],
            'delivery_address' => $orderData['delivery_address'] ?: $merchant->location_address,
            'assigned_workflow_id' => $workflow->id,
            'payment_terms_override' => null, // Use merchant default
            // Catalog order specific fields
            'catalog_order' => $orderData['catalog_order'] ?? false,
            'catalog_id' => $orderData['catalog_id'] ?? null,
            'catalog_total' => $orderData['catalog_total'] ?? null,
        ]);
    }

    /**
     * Smart workflow selection based on order characteristics
     */
    private function selectWorkflow(array $orderData, array $pricingResult): Workflow
    {
        // Check if it's a rush order (same day delivery)
        $deliveryDate = $orderData['delivery_date'] ? 
            \Carbon\Carbon::parse($orderData['delivery_date']) : 
            now()->addDay();
            
        if ($deliveryDate->isToday()) {
            $workflow = Workflow::where('workflow_type', 'rush')
                               ->where('active_status', true)
                               ->first();
            if ($workflow) return $workflow;
        }
        
        // Check if it's a large order (>$500)
        if ($pricingResult['total'] > 500) {
            $workflow = Workflow::where('workflow_type', 'custom')
                               ->where('active_status', true)
                               ->first();
            if ($workflow) return $workflow;
        }
        
        // Default to standard workflow
        return Workflow::where('workflow_type', 'standard')
                      ->where('active_status', true)
                      ->firstOrFail();
    }

    /**
     * Update product tracking for FIFO management
     */
    private function updateProductTracking(Merchant $merchant, array $items, $deliveryDate): void
    {
        foreach ($items as $item) {
            // This will be handled by the waste management service
            // when the delivery is actually completed
        }
    }

    /**
     * Process manual order (from admin interface)
     */
    public function processManualOrder(array $orderData): array
    {
        return DB::transaction(function () use ($orderData) {
            $merchant = Merchant::findOrFail($orderData['merchant_id']);
            
            // Similar validation but with admin override capabilities
            $order = Order::create([
                'merchant_id' => $merchant->id,
                'whatsapp_order_id' => null,
                'order_items' => $orderData['items'],
                'total_amount' => $orderData['total_amount'],
                'order_date' => now(),
                'requested_delivery_date' => $orderData['delivery_date'],
                'order_status' => $orderData['status'] ?? 'pending',
                'special_notes' => $orderData['special_notes'] ?? null,
                'delivery_address' => $orderData['delivery_address'],
                'assigned_workflow_id' => $orderData['workflow_id'],
                'payment_terms_override' => $orderData['payment_terms_override'] ?? null,
            ]);

            // Create job ticket if order is confirmed
            $jobTicket = null;
            if ($order->order_status === 'confirmed') {
                $jobTicket = $this->jobTicketService->createFromOrder($order);
            }

            return [
                'success' => true,
                'order' => $order,
                'job_ticket' => $jobTicket
            ];
        });
    }

    /**
     * Update order status with business logic
     */
    public function updateOrderStatus(Order $order, string $newStatus): array
    {
        $oldStatus = $order->order_status;
        
        // Business logic for status transitions
        switch ($newStatus) {
            case 'confirmed':
                if ($oldStatus === 'pending') {
                    $order->update(['order_status' => $newStatus]);
                    
                    // Create job ticket when order is confirmed
                    if (!$order->jobTicket) {
                        $jobTicket = $this->jobTicketService->createFromOrder($order);
                        return ['success' => true, 'job_ticket_created' => true, 'job_ticket' => $jobTicket];
                    }
                }
                break;
                
            case 'cancelled':
                if (in_array($oldStatus, ['pending', 'confirmed'])) {
                    $order->update(['order_status' => $newStatus]);
                    
                    // Cancel associated job ticket
                    if ($order->jobTicket && $order->jobTicket->current_status !== 'completed') {
                        $this->jobTicketService->cancelJobTicket($order->jobTicket);
                    }
                }
                break;
        }
        
        return ['success' => true];
    }
}