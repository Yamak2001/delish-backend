<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantProductTracking;
use App\Models\WasteManagement;
use App\Models\Recipe;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WasteManagementService
{
    /**
     * Process reorder with FIFO logic - the smart algorithm that prevents unnecessary waste collection
     */
    public function processReorder(Merchant $merchant, array $orderItems): array
    {
        $updatedItems = [];
        $wastePreventionSavings = 0;
        
        foreach ($orderItems as $orderItem) {
            $recipeId = $orderItem['recipe_id'];
            $newQuantity = $orderItem['quantity'];
            
            // Get existing products at merchant location (FIFO order)
            $existingProducts = MerchantProductTracking::where('merchant_id', $merchant->id)
                ->where('recipe_id', $recipeId)
                ->where('current_estimated_quantity', '>', 0)
                ->where('status', '!=', 'expired')
                ->orderBy('delivery_date', 'asc') // FIFO - oldest first
                ->get();
            
            if ($existingProducts->isNotEmpty()) {
                $result = $this->applyFifoReorderLogic($merchant, $recipeId, $newQuantity, $existingProducts);
                
                if ($result['waste_prevented']) {
                    $updatedItems[] = [
                        'recipe_id' => $recipeId,
                        'recipe_name' => $orderItem['recipe_name'],
                        'new_quantity' => $newQuantity,
                        'waste_prevented_quantity' => $result['waste_prevented_quantity'],
                        'estimated_savings' => $result['estimated_savings'],
                        'updated_tracking_records' => $result['updated_records']
                    ];
                    
                    $wastePreventionSavings += $result['estimated_savings'];
                }
            }
        }
        
        // Cancel unnecessary waste collection appointments
        if (!empty($updatedItems)) {
            $this->cancelUnnecessaryWasteCollections($merchant, $updatedItems);
        }
        
        Log::info("FIFO reorder processing completed", [
            'merchant_id' => $merchant->id,
            'items_processed' => count($updatedItems),
            'total_savings' => $wastePreventionSavings
        ]);
        
        return [
            'updated_items' => $updatedItems,
            'total_savings' => $wastePreventionSavings,
            'waste_collections_cancelled' => !empty($updatedItems)
        ];
    }

    /**
     * Apply FIFO logic when merchant reorders the same products
     */
    private function applyFifoReorderLogic(
        Merchant $merchant, 
        int $recipeId, 
        int $newQuantity, 
        $existingProducts
    ): array {
        $totalExistingQuantity = $existingProducts->sum('current_estimated_quantity');
        $wastePreventedQuantity = 0;
        $estimatedSavings = 0;
        $updatedRecords = [];
        
        // Calculate how much existing product will be "sold" due to new order
        $sellThroughQuantity = min($totalExistingQuantity, $newQuantity);
        
        if ($sellThroughQuantity > 0) {
            // Apply FIFO sales logic - oldest products get sold first
            $remainingSellThrough = $sellThroughQuantity;
            
            foreach ($existingProducts as $productRecord) {
                if ($remainingSellThrough <= 0) break;
                
                $currentQuantity = $productRecord->current_estimated_quantity;
                $quantityToSell = min($currentQuantity, $remainingSellThrough);
                
                // Update the tracking record
                $newEstimatedQuantity = $currentQuantity - $quantityToSell;
                
                $productRecord->update([
                    'current_estimated_quantity' => $newEstimatedQuantity,
                    'status' => $newEstimatedQuantity > 0 ? 
                        $this->calculateProductStatus($productRecord->expiration_date) : 'sold_out'
                ]);
                
                $updatedRecords[] = [
                    'tracking_id' => $productRecord->id,
                    'previous_quantity' => $currentQuantity,
                    'new_quantity' => $newEstimatedQuantity,
                    'quantity_sold' => $quantityToSell
                ];
                
                $wastePreventedQuantity += $quantityToSell;
                $remainingSellThrough -= $quantityToSell;
                
                // Calculate estimated savings (assuming the product would have been wasted)
                if ($productRecord->expiration_date->isPast() || 
                    $productRecord->expiration_date->diffInDays(now()) <= 1) {
                    
                    $recipe = Recipe::find($recipeId);
                    $estimatedValue = $recipe ? ($recipe->cost_per_unit * $quantityToSell) : 0;
                    $estimatedSavings += $estimatedValue;
                }
            }
        }
        
        return [
            'waste_prevented' => $wastePreventedQuantity > 0,
            'waste_prevented_quantity' => $wastePreventedQuantity,
            'estimated_savings' => round($estimatedSavings, 2),
            'updated_records' => $updatedRecords
        ];
    }

    /**
     * Generate automated waste alerts based on expiration dates
     */
    public function generateWasteAlerts(): array
    {
        $alerts = [];
        
        // Find products expiring in next 2 days
        $expiringProducts = MerchantProductTracking::with(['merchant', 'recipe'])
            ->where('current_estimated_quantity', '>', 0)
            ->where('expiration_date', '<=', now()->addDays(2))
            ->where('status', '!=', 'expired')
            ->orderBy('expiration_date', 'asc')
            ->get();
        
        foreach ($expiringProducts as $product) {
            $daysUntilExpiration = now()->diffInDays($product->expiration_date, false);
            
            // Update status based on expiration
            $newStatus = $this->calculateProductStatus($product->expiration_date);
            if ($product->status !== $newStatus) {
                $product->update(['status' => $newStatus]);
            }
            
            $alerts[] = [
                'merchant_id' => $product->merchant_id,
                'merchant_name' => $product->merchant->business_name,
                'recipe_id' => $product->recipe_id,
                'recipe_name' => $product->recipe->recipe_name,
                'quantity' => $product->current_estimated_quantity,
                'expiration_date' => $product->expiration_date->format('Y-m-d'),
                'days_until_expiration' => $daysUntilExpiration,
                'status' => $newStatus,
                'estimated_waste_value' => $product->recipe->cost_per_unit * $product->current_estimated_quantity,
                'collection_required' => $daysUntilExpiration <= 0
            ];
        }
        
        // Schedule waste collection for expired items
        $this->scheduleWasteCollection($alerts);
        
        return $alerts;
    }

    /**
     * Schedule automatic waste collection
     */
    public function scheduleWasteCollection(array $wasteAlerts): void
    {
        // Group alerts by merchant for efficient collection routes
        $merchantAlerts = collect($wasteAlerts)
            ->where('collection_required', true)
            ->groupBy('merchant_id');
        
        foreach ($merchantAlerts as $merchantId => $alerts) {
            $merchant = Merchant::find($merchantId);
            if (!$merchant) continue;
            
            // Check if there's already a scheduled collection for this merchant
            $existingCollection = WasteManagement::where('merchant_id', $merchantId)
                ->whereIn('collection_status', ['scheduled', 'in_progress'])
                ->where('scheduled_collection_date', '>=', today())
                ->first();
            
            if (!$existingCollection) {
                $wasteItems = [];
                $totalWasteValue = 0;
                
                foreach ($alerts as $alert) {
                    $wasteItems[] = [
                        'recipe_id' => $alert['recipe_id'],
                        'recipe_name' => $alert['recipe_name'],
                        'quantity' => $alert['quantity'],
                        'expiration_date' => $alert['expiration_date'],
                        'condition' => 'expired'
                    ];
                    
                    $totalWasteValue += $alert['estimated_waste_value'];
                }
                
                // Schedule collection for next business day
                WasteManagement::create([
                    'merchant_id' => $merchantId,
                    'scheduled_collection_date' => $this->getNextCollectionDate(),
                    'assigned_driver_id' => null, // Will be assigned later
                    'collection_status' => 'scheduled',
                    'waste_items_collected' => $wasteItems,
                    'total_waste_value' => round($totalWasteValue, 2),
                    'credited_to_merchant' => false,
                ]);
                
                // Mark products as requiring collection
                MerchantProductTracking::where('merchant_id', $merchantId)
                    ->whereIn('recipe_id', $alerts->pluck('recipe_id'))
                    ->where('status', 'expired')
                    ->update(['collection_required' => true]);
                
                Log::info("Waste collection scheduled", [
                    'merchant_id' => $merchantId,
                    'collection_date' => $this->getNextCollectionDate(),
                    'total_value' => $totalWasteValue,
                    'items_count' => count($wasteItems)
                ]);
            }
        }
    }

    /**
     * Cancel unnecessary waste collections when reorders prevent waste
     */
    private function cancelUnnecessaryWasteCollections(Merchant $merchant, array $updatedItems): void
    {
        $recipesWithPreventedWaste = collect($updatedItems)->pluck('recipe_id')->toArray();
        
        // Find scheduled collections that might no longer be needed
        $scheduledCollections = WasteManagement::where('merchant_id', $merchant->id)
            ->where('collection_status', 'scheduled')
            ->where('scheduled_collection_date', '>=', today())
            ->get();
        
        foreach ($scheduledCollections as $collection) {
            $wasteItems = $collection->waste_items_collected ?? [];
            $remainingWasteItems = [];
            $updatedWasteValue = 0;
            
            foreach ($wasteItems as $wasteItem) {
                // Check if this recipe had waste prevented
                if (!in_array($wasteItem['recipe_id'], $recipesWithPreventedWaste)) {
                    $remainingWasteItems[] = $wasteItem;
                    $recipe = Recipe::find($wasteItem['recipe_id']);
                    if ($recipe) {
                        $updatedWasteValue += $recipe->cost_per_unit * $wasteItem['quantity'];
                    }
                }
            }
            
            if (empty($remainingWasteItems)) {
                // Cancel the entire collection
                $collection->update([
                    'collection_status' => 'cancelled',
                    'actual_collection_date' => now(),
                ]);
                
                Log::info("Waste collection cancelled - waste prevented by reorder", [
                    'collection_id' => $collection->id,
                    'merchant_id' => $merchant->id
                ]);
            } else if (count($remainingWasteItems) < count($wasteItems)) {
                // Update collection with remaining items only
                $collection->update([
                    'waste_items_collected' => $remainingWasteItems,
                    'total_waste_value' => round($updatedWasteValue, 2),
                ]);
            }
        }
    }

    /**
     * Calculate product status based on expiration date
     */
    private function calculateProductStatus(Carbon $expirationDate): string
    {
        $daysUntilExpiration = now()->diffInDays($expirationDate, false);
        
        if ($daysUntilExpiration < 0) {
            return 'expired';
        } elseif ($daysUntilExpiration <= 1) {
            return 'warning';
        } else {
            return 'fresh';
        }
    }

    /**
     * Get next available collection date
     */
    private function getNextCollectionDate(): Carbon
    {
        $date = now()->addDay();
        
        // Skip weekends (assuming Monday-Friday collections)
        while ($date->isWeekend()) {
            $date->addDay();
        }
        
        return $date;
    }

    /**
     * Complete waste collection and generate credit note
     */
    public function completeWasteCollection(
        WasteManagement $collection, 
        array $actualWasteCollected, 
        ?string $driverNotes = null,
        array $photos = []
    ): array {
        
        return DB::transaction(function () use ($collection, $actualWasteCollected, $driverNotes, $photos) {
            
            // Update collection record
            $collection->update([
                'collection_status' => 'completed',
                'actual_collection_date' => now(),
                'waste_items_collected' => $actualWasteCollected,
                'photos' => $photos,
                'total_waste_value' => $this->calculateWasteValue($actualWasteCollected),
                'credited_to_merchant' => true,
            ]);
            
            // Update product tracking records
            foreach ($actualWasteCollected as $wasteItem) {
                MerchantProductTracking::where('merchant_id', $collection->merchant_id)
                    ->where('recipe_id', $wasteItem['recipe_id'])
                    ->where('collection_required', true)
                    ->update([
                        'current_estimated_quantity' => 0,
                        'status' => 'collected',
                        'collection_required' => false,
                    ]);
            }
            
            // Generate credit note for merchant
            $creditAmount = $collection->total_waste_value;
            if ($creditAmount > 0) {
                $this->generateWasteCreditNote($collection->merchant, $creditAmount, $actualWasteCollected);
            }
            
            Log::info("Waste collection completed", [
                'collection_id' => $collection->id,
                'merchant_id' => $collection->merchant_id,
                'items_collected' => count($actualWasteCollected),
                'credit_amount' => $creditAmount
            ]);
            
            return [
                'success' => true,
                'collection' => $collection,
                'credit_amount' => $creditAmount,
                'items_collected' => count($actualWasteCollected)
            ];
        });
    }

    /**
     * Calculate total value of collected waste
     */
    private function calculateWasteValue(array $wasteItems): float
    {
        $totalValue = 0;
        
        foreach ($wasteItems as $item) {
            $recipe = Recipe::find($item['recipe_id']);
            if ($recipe) {
                $totalValue += $recipe->cost_per_unit * $item['quantity'];
            }
        }
        
        return round($totalValue, 2);
    }

    /**
     * Generate credit note for waste collection
     */
    private function generateWasteCreditNote(Merchant $merchant, float $creditAmount, array $wasteItems): void
    {
        // TODO: Integrate with Invoice service to generate credit note
        Log::info("Waste credit note generated", [
            'merchant_id' => $merchant->id,
            'credit_amount' => $creditAmount,
            'waste_items' => count($wasteItems)
        ]);
    }

    /**
     * Get waste analytics for merchant
     */
    public function getWasteAnalytics(Merchant $merchant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: now()->subMonth();
        $endDate = $endDate ?: now();
        
        $wasteCollections = WasteManagement::where('merchant_id', $merchant->id)
            ->whereBetween('actual_collection_date', [$startDate, $endDate])
            ->where('collection_status', 'completed')
            ->get();
        
        $totalWasteValue = $wasteCollections->sum('total_waste_value');
        $totalCollections = $wasteCollections->count();
        $wastePreventionSavings = $this->calculateWastePreventionSavings($merchant, $startDate, $endDate);
        
        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ],
            'total_waste_value' => $totalWasteValue,
            'total_collections' => $totalCollections,
            'avg_waste_per_collection' => $totalCollections > 0 ? round($totalWasteValue / $totalCollections, 2) : 0,
            'waste_prevention_savings' => $wastePreventionSavings,
            'net_waste_impact' => round($totalWasteValue - $wastePreventionSavings, 2),
            'collections' => $wasteCollections->map(function ($collection) {
                return [
                    'collection_date' => $collection->actual_collection_date->format('Y-m-d'),
                    'items_collected' => count($collection->waste_items_collected ?? []),
                    'waste_value' => $collection->total_waste_value
                ];
            })
        ];
    }

    /**
     * Calculate estimated waste prevention savings from FIFO logic
     */
    private function calculateWastePreventionSavings(Merchant $merchant, Carbon $startDate, Carbon $endDate): float
    {
        // This would track savings from reorder logic
        // For now, return a placeholder calculation
        return 0; // TODO: Implement based on historical reorder data
    }
}