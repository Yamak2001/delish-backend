<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MerchantPricing;
use App\Models\Merchant;
use App\Models\Recipe;

class MerchantPricingSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::where('account_status', 'active')->get();
        $recipes = Recipe::where('status', 'active')->get();

        $pricingData = [];

        foreach ($merchants as $merchant) {
            // Determine pricing tier based on merchant type and volume
            $pricingTier = $this->determinePricingTier($merchant);
            
            foreach ($recipes as $recipe) {
                $basePrice = $recipe->selling_price;
                $merchantPrice = $this->calculateMerchantPrice($basePrice, $pricingTier, $merchant->merchant_type);
                $volumeDiscounts = $this->getVolumeDiscounts($pricingTier);

                $pricingData[] = [
                    'merchant_id' => $merchant->id,
                    'recipe_id' => $recipe->id,
                    'pricing_tier' => $pricingTier,
                    'base_price' => $merchantPrice,
                    'volume_discount_rules' => json_encode($volumeDiscounts),
                    'minimum_order_quantity' => $this->getMinimumOrderQuantity($pricingTier, $recipe->product_category),
                    'effective_start_date' => now()->startOfMonth(),
                    'effective_end_date' => now()->endOfYear(),
                    'status' => 'active',
                ];
            }
        }

        foreach ($pricingData as $pricing) {
            MerchantPricing::create($pricing);
        }

        $this->command->info('Created ' . count($pricingData) . ' merchant pricing records');
    }

    private function determinePricingTier(Merchant $merchant): string
    {
        // Determine pricing tier based on merchant characteristics
        $creditLimit = $merchant->credit_limit;
        $businessName = $merchant->business_name;
        $paymentTerms = $merchant->payment_terms;

        // Determine merchant type from business name
        $businessNameLower = strtolower($businessName);
        $merchantType = 'basic';
        
        if (str_contains($businessNameLower, 'hotel')) $merchantType = 'hotel';
        elseif (str_contains($businessNameLower, 'catering')) $merchantType = 'catering';
        elseif (str_contains($businessNameLower, 'restaurant')) $merchantType = 'restaurant';
        elseif (str_contains($businessNameLower, 'cafe')) $merchantType = 'cafe';
        elseif (str_contains($businessNameLower, 'bakery')) $merchantType = 'bakery';
        else $merchantType = 'retail';

        if ($creditLimit >= 15000 && in_array($merchantType, ['hotel', 'catering']) && $paymentTerms === 'net_15') {
            return 'premium';
        } elseif ($creditLimit >= 10000 && in_array($merchantType, ['restaurant', 'catering'])) {
            return 'volume';
        } elseif ($creditLimit >= 5000) {
            return 'standard';
        } else {
            return 'basic';
        }
    }

    private function calculateMerchantPrice(float $basePrice, string $pricingTier, string $merchantType): float
    {
        $discountMultipliers = [
            'premium' => 0.75,  // 25% discount
            'volume' => 0.80,   // 20% discount
            'standard' => 0.85, // 15% discount
            'basic' => 0.90,    // 10% discount
        ];

        // Additional discount for certain merchant types
        $typeDiscounts = [
            'hotel' => 0.95,     // Additional 5% off
            'catering' => 0.92,  // Additional 8% off
            'restaurant' => 0.97, // Additional 3% off
            'bakery' => 1.00,    // No additional discount
            'cafe' => 1.00,      // No additional discount
            'retail' => 1.00,    // No additional discount
        ];

        $tierMultiplier = $discountMultipliers[$pricingTier] ?? 0.90;
        $typeMultiplier = $typeDiscounts[$merchantType] ?? 1.00;

        return round($basePrice * $tierMultiplier * $typeMultiplier, 2);
    }

    private function getVolumeDiscounts(string $pricingTier): array
    {
        $volumeDiscountRules = [
            'premium' => [
                ['min_quantity' => 5, 'discount_percentage' => 10],
                ['min_quantity' => 15, 'discount_percentage' => 15],
                ['min_quantity' => 30, 'discount_percentage' => 20],
                ['min_quantity' => 50, 'discount_percentage' => 25],
            ],
            'volume' => [
                ['min_quantity' => 10, 'discount_percentage' => 5],
                ['min_quantity' => 25, 'discount_percentage' => 10],
                ['min_quantity' => 50, 'discount_percentage' => 15],
            ],
            'standard' => [
                ['min_quantity' => 10, 'discount_percentage' => 5],
                ['min_quantity' => 25, 'discount_percentage' => 10],
            ],
            'basic' => [
                ['min_quantity' => 12, 'discount_percentage' => 5],
            ],
        ];

        return $volumeDiscountRules[$pricingTier] ?? [];
    }

    private function getMinimumOrderQuantity(string $pricingTier, string $productCategory): int
    {
        $minimumOrders = [
            'cakes' => [
                'premium' => 1,
                'volume' => 1,
                'standard' => 1,
                'basic' => 2,
            ],
            'cupcakes' => [
                'premium' => 6,
                'volume' => 12,
                'standard' => 12,
                'basic' => 24,
            ],
            'cookies' => [
                'premium' => 12,
                'volume' => 24,
                'standard' => 24,
                'basic' => 48,
            ],
            'cheesecakes' => [
                'premium' => 1,
                'volume' => 1,
                'standard' => 1,
                'basic' => 2,
            ],
            'pies' => [
                'premium' => 1,
                'volume' => 2,
                'standard' => 2,
                'basic' => 3,
            ],
            'bars' => [
                'premium' => 12,
                'volume' => 24,
                'standard' => 24,
                'basic' => 48,
            ],
        ];

        return $minimumOrders[$productCategory][$pricingTier] ?? 1;
    }
}