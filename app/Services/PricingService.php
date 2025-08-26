<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Recipe;
use App\Models\MerchantPricing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate order total with merchant-specific pricing matrix
     */
    public function calculateOrderTotal(Merchant $merchant, array $orderItems): array
    {
        $totalAmount = 0;
        $pricingBreakdown = [];
        $missingPricing = [];

        foreach ($orderItems as $item) {
            $recipeId = $item['recipe_id'];
            $quantity = $item['quantity'];
            
            // Get merchant-specific price for this recipe
            $pricing = $this->getMerchantRecipePrice($merchant, $recipeId);
            
            if (!$pricing) {
                $missingPricing[] = $item['recipe_name'] ?? "Recipe ID: {$recipeId}";
                continue;
            }
            
            // Apply quantity-based discounts
            $unitPrice = $this->applyQuantityDiscount($pricing, $quantity);
            $lineTotal = $unitPrice * $quantity;
            
            $totalAmount += $lineTotal;
            $pricingBreakdown[] = [
                'recipe_id' => $recipeId,
                'recipe_name' => $item['recipe_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'discount_applied' => $unitPrice !== $pricing['merchant_price'],
                'price_tier' => $pricing['price_tier']
            ];
        }
        
        if (!empty($missingPricing)) {
            return [
                'success' => false,
                'message' => 'Missing pricing for: ' . implode(', ', $missingPricing) . 
                           '. Please contact admin to set up pricing for these items.'
            ];
        }
        
        return [
            'success' => true,
            'total' => round($totalAmount, 2),
            'breakdown' => $pricingBreakdown,
            'tax_amount' => 0, // Add tax calculation if needed
            'discount_amount' => $this->calculateTotalDiscount($pricingBreakdown)
        ];
    }

    /**
     * Get merchant-specific price for recipe with caching
     */
    public function getMerchantRecipePrice(Merchant $merchant, int $recipeId): ?array
    {
        $cacheKey = "pricing.merchant.{$merchant->id}.recipe.{$recipeId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($merchant, $recipeId) {
            // Get active pricing record
            $pricing = MerchantPricing::where('merchant_id', $merchant->id)
                ->where('recipe_id', $recipeId)
                ->where('effective_date', '<=', today())
                ->where(function ($query) {
                    $query->whereNull('expiration_date')
                          ->orWhere('expiration_date', '>=', today());
                })
                ->orderBy('effective_date', 'desc')
                ->first();
                
            if ($pricing) {
                return [
                    'merchant_price' => (float) $pricing->merchant_price,
                    'base_cost' => (float) $pricing->base_cost,
                    'markup_percentage' => (float) $pricing->markup_percentage,
                    'price_tier' => $pricing->price_tier,
                    'effective_date' => $pricing->effective_date->format('Y-m-d'),
                ];
            }
            
            // Fall back to default pricing if no merchant-specific pricing exists
            return $this->getDefaultRecipePrice($recipeId);
        });
    }

    /**
     * Apply quantity-based discount tiers
     */
    private function applyQuantityDiscount(array $pricing, int $quantity): float
    {
        $basePrice = $pricing['merchant_price'];
        $tier = $pricing['price_tier'];
        
        // Volume discount rules
        $discountRules = [
            'standard' => [
                10 => 0.05,  // 5% off for 10+ items
                25 => 0.10,  // 10% off for 25+ items
                50 => 0.15,  // 15% off for 50+ items
            ],
            'volume' => [
                5 => 0.10,   // 10% off for 5+ items
                15 => 0.15,  // 15% off for 15+ items
                30 => 0.20,  // 20% off for 30+ items
            ],
            'premium' => [
                // Premium tier already discounted, no additional volume discounts
            ]
        ];
        
        $applicableDiscounts = $discountRules[$tier] ?? [];
        $discount = 0;
        
        foreach ($applicableDiscounts as $minQuantity => $discountPercent) {
            if ($quantity >= $minQuantity) {
                $discount = $discountPercent;
            }
        }
        
        return $basePrice * (1 - $discount);
    }

    /**
     * Set merchant pricing for recipe
     */
    public function setMerchantPricing(
        Merchant $merchant, 
        Recipe $recipe, 
        float $price, 
        string $tier = 'standard',
        ?Carbon $effectiveDate = null,
        ?Carbon $expirationDate = null
    ): MerchantPricing {
        
        // Calculate base cost from recipe ingredients
        $baseCost = $this->calculateRecipeBaseCost($recipe);
        
        // Calculate markup percentage
        $markupPercentage = (($price - $baseCost) / $baseCost) * 100;
        
        // Expire any existing pricing for this combination
        MerchantPricing::where('merchant_id', $merchant->id)
            ->where('recipe_id', $recipe->id)
            ->whereNull('expiration_date')
            ->update(['expiration_date' => now()->subDay()]);
        
        // Create new pricing record
        $pricing = MerchantPricing::create([
            'merchant_id' => $merchant->id,
            'recipe_id' => $recipe->id,
            'base_cost' => $baseCost,
            'merchant_price' => $price,
            'markup_percentage' => round($markupPercentage, 2),
            'effective_date' => $effectiveDate ?: today(),
            'expiration_date' => $expirationDate,
            'price_tier' => $tier,
            'created_by_user_id' => auth()->id(),
        ]);
        
        // Clear cache for this merchant-recipe combination
        $cacheKey = "pricing.merchant.{$merchant->id}.recipe.{$recipe->id}";
        Cache::forget($cacheKey);
        
        Log::info("Merchant pricing updated", [
            'merchant_id' => $merchant->id,
            'recipe_id' => $recipe->id,
            'new_price' => $price,
            'markup_percentage' => $markupPercentage,
            'tier' => $tier
        ]);
        
        return $pricing;
    }

    /**
     * Bulk update merchant pricing
     */
    public function bulkUpdateMerchantPricing(Merchant $merchant, array $pricingData): array
    {
        $results = [];
        $errors = [];
        
        foreach ($pricingData as $item) {
            try {
                $recipe = Recipe::findOrFail($item['recipe_id']);
                
                $pricing = $this->setMerchantPricing(
                    $merchant,
                    $recipe,
                    $item['price'],
                    $item['tier'] ?? 'standard',
                    isset($item['effective_date']) ? Carbon::parse($item['effective_date']) : null,
                    isset($item['expiration_date']) ? Carbon::parse($item['expiration_date']) : null
                );
                
                $results[] = [
                    'recipe_id' => $recipe->id,
                    'recipe_name' => $recipe->recipe_name,
                    'price' => $pricing->merchant_price,
                    'status' => 'success'
                ];
                
            } catch (\Exception $e) {
                $errors[] = [
                    'recipe_id' => $item['recipe_id'] ?? null,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }
        
        return [
            'success' => empty($errors),
            'results' => $results,
            'errors' => $errors,
            'total_updated' => count($results)
        ];
    }

    /**
     * Get pricing matrix for merchant (all recipes)
     */
    public function getMerchantPricingMatrix(Merchant $merchant): array
    {
        $pricing = MerchantPricing::with('recipe')
            ->where('merchant_id', $merchant->id)
            ->where('effective_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>=', today());
            })
            ->orderBy('recipe_id')
            ->get();
        
        $matrix = [];
        foreach ($pricing as $price) {
            $matrix[] = [
                'recipe_id' => $price->recipe_id,
                'recipe_name' => $price->recipe->recipe_name,
                'base_cost' => $price->base_cost,
                'merchant_price' => $price->merchant_price,
                'markup_percentage' => $price->markup_percentage,
                'price_tier' => $price->price_tier,
                'effective_date' => $price->effective_date->format('Y-m-d'),
                'expiration_date' => $price->expiration_date?->format('Y-m-d'),
            ];
        }
        
        return $matrix;
    }

    /**
     * Calculate competitive pricing analysis
     */
    public function getCompetitivePricingAnalysis(Recipe $recipe): array
    {
        $pricingData = MerchantPricing::where('recipe_id', $recipe->id)
            ->where('effective_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>=', today());
            })
            ->get();
        
        if ($pricingData->isEmpty()) {
            return ['error' => 'No pricing data available for this recipe'];
        }
        
        $prices = $pricingData->pluck('merchant_price')->toArray();
        
        return [
            'recipe_name' => $recipe->recipe_name,
            'base_cost' => $this->calculateRecipeBaseCost($recipe),
            'min_price' => min($prices),
            'max_price' => max($prices),
            'avg_price' => round(array_sum($prices) / count($prices), 2),
            'median_price' => $this->calculateMedian($prices),
            'total_merchants' => $pricingData->count(),
            'price_distribution' => $this->getPriceDistribution($pricingData)
        ];
    }

    /**
     * Get default recipe price (fallback)
     */
    private function getDefaultRecipePrice(int $recipeId): ?array
    {
        $recipe = Recipe::find($recipeId);
        if (!$recipe) return null;
        
        $baseCost = $this->calculateRecipeBaseCost($recipe);
        $defaultMarkup = 2.0; // 100% markup as default
        
        return [
            'merchant_price' => $baseCost * $defaultMarkup,
            'base_cost' => $baseCost,
            'markup_percentage' => 100.0,
            'price_tier' => 'standard',
            'effective_date' => today()->format('Y-m-d'),
        ];
    }

    /**
     * Calculate recipe base cost from ingredients
     */
    private function calculateRecipeBaseCost(Recipe $recipe): float
    {
        return $recipe->recipeIngredients->sum(function ($ingredient) {
            return $ingredient->getCostContribution();
        });
    }

    /**
     * Calculate total discount amount
     */
    private function calculateTotalDiscount(array $pricingBreakdown): float
    {
        $totalDiscount = 0;
        
        foreach ($pricingBreakdown as $item) {
            if ($item['discount_applied']) {
                // Calculate original price vs discounted price
                $originalTotal = $item['quantity'] * ($item['unit_price'] / 0.9); // Assuming average 10% discount
                $actualTotal = $item['line_total'];
                $totalDiscount += ($originalTotal - $actualTotal);
            }
        }
        
        return round($totalDiscount, 2);
    }

    /**
     * Calculate median of array
     */
    private function calculateMedian(array $prices): float
    {
        sort($prices);
        $count = count($prices);
        $middle = floor($count / 2);
        
        if ($count % 2) {
            return $prices[$middle];
        } else {
            return ($prices[$middle - 1] + $prices[$middle]) / 2;
        }
    }

    /**
     * Get price distribution by tier
     */
    private function getPriceDistribution($pricingData): array
    {
        $distribution = [];
        
        foreach ($pricingData->groupBy('price_tier') as $tier => $prices) {
            $distribution[$tier] = [
                'count' => $prices->count(),
                'avg_price' => $prices->avg('merchant_price'),
                'avg_markup' => $prices->avg('markup_percentage')
            ];
        }
        
        return $distribution;
    }
}