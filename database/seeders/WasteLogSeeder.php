<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WasteLog;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\User;

class WasteLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('role', ['admin', 'manager'])->get();
        $inventoryItems = InventoryItem::all();
        $orders = Order::all();

        if ($users->isEmpty() || $inventoryItems->isEmpty()) {
            $this->command->warn('Skipping WasteLogSeeder - missing dependencies');
            return;
        }

        $wasteLogsData = [
            [
                'item_name' => 'Premium Wheat Flour',
                'waste_type' => 'expired',
                'waste_source' => 'storage',
                'quantity_wasted' => 25.500,
                'unit_cost' => 1.45,
                'waste_date' => '2025-08-20',
                'waste_reason' => 'Exceeded expiry date - storage humidity caused early spoilage',
                'prevention_notes' => 'Improve storage ventilation and implement better FIFO rotation',
                'disposal_method' => 'compost'
            ],
            [
                'item_name' => 'Fresh Whole Milk',
                'waste_type' => 'expired',
                'waste_source' => 'kitchen',
                'quantity_wasted' => 12.000,
                'unit_cost' => 1.20,
                'waste_date' => '2025-08-22',
                'waste_reason' => 'Refrigeration failure overnight - temperature rose above safe levels',
                'prevention_notes' => 'Install backup refrigeration alarm system',
                'disposal_method' => 'landfill'
            ],
            [
                'item_name' => 'Chocolate Cake',
                'waste_type' => 'overproduction',
                'waste_source' => 'kitchen',
                'quantity_wasted' => 2.000,
                'unit_cost' => 25.00,
                'waste_date' => '2025-08-23',
                'waste_reason' => 'Customer cancelled large order last minute - unable to resell in time',
                'prevention_notes' => 'Implement better order confirmation process and backup customer list',
                'disposal_method' => 'donation'
            ],
            [
                'item_name' => 'Vanilla Cupcakes',
                'waste_type' => 'quality_control',
                'waste_source' => 'quality_check',
                'quantity_wasted' => 18.000,
                'unit_cost' => 2.50,
                'waste_date' => '2025-08-24',
                'waste_reason' => 'Failed quality inspection - uneven baking and texture issues',
                'prevention_notes' => 'Calibrate oven temperature settings and review mixing procedures',
                'disposal_method' => 'compost'
            ],
            [
                'item_name' => 'Heavy Cream',
                'waste_type' => 'damaged',
                'waste_source' => 'delivery',
                'quantity_wasted' => 5.000,
                'unit_cost' => 2.80,
                'waste_date' => '2025-08-25',
                'waste_reason' => 'Packaging damaged during delivery - container leaked',
                'prevention_notes' => 'Better packaging protection and delivery handling training',
                'disposal_method' => 'landfill'
            ],
            [
                'item_name' => 'Wedding Cake',
                'waste_type' => 'returned',
                'waste_source' => 'customer_return',
                'quantity_wasted' => 1.000,
                'unit_cost' => 150.00,
                'waste_date' => '2025-08-25',
                'waste_reason' => 'Customer claimed wrong flavor - ordered chocolate, received vanilla',
                'prevention_notes' => 'Double-check order specifications before production start',
                'disposal_method' => 'donation'
            ],
            [
                'item_name' => 'Granulated Sugar',
                'waste_type' => 'damaged',
                'waste_source' => 'storage',
                'quantity_wasted' => 8.750,
                'unit_cost' => 1.95,
                'waste_date' => '2025-08-24',
                'waste_reason' => 'Water leak from ceiling damaged storage bags',
                'prevention_notes' => 'Fix roof leak and relocate sugar storage to secure area',
                'disposal_method' => 'landfill'
            ],
            [
                'item_name' => 'Brownies',
                'waste_type' => 'spoiled',
                'waste_source' => 'storage',
                'quantity_wasted' => 15.000,
                'unit_cost' => 3.00,
                'waste_date' => '2025-08-26',
                'waste_reason' => 'Mold growth due to high humidity in display case',
                'prevention_notes' => 'Install dehumidifier in display area and reduce display time',
                'disposal_method' => 'compost'
            ],
            [
                'item_name' => 'Cocoa Powder',
                'waste_type' => 'other',
                'waste_source' => 'kitchen',
                'quantity_wasted' => 2.250,
                'unit_cost' => 8.25,
                'waste_date' => '2025-08-21',
                'waste_reason' => 'Container accidentally dropped and contents contaminated with floor debris',
                'prevention_notes' => 'Implement better handling procedures and non-slip flooring',
                'disposal_method' => 'landfill'
            ],
            [
                'item_name' => 'Lemon Bars',
                'waste_type' => 'overproduction',
                'waste_source' => 'kitchen',
                'quantity_wasted' => 12.000,
                'unit_cost' => 2.75,
                'waste_date' => '2025-08-26',
                'waste_reason' => 'Seasonal item - demand lower than expected for winter period',
                'prevention_notes' => 'Adjust seasonal production planning based on historical data',
                'disposal_method' => 'donation'
            ]
        ];

        foreach ($wasteLogsData as $index => $wasteData) {
            $inventoryItem = $inventoryItems->where('name', 'LIKE', '%' . explode(' ', $wasteData['item_name'])[0] . '%')->first();
            $reportedBy = $users->random();
            $approvedBy = $users->where('role', 'admin')->first() ?? $users->first();

            WasteLog::create([
                'waste_log_number' => 'WL-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'inventory_item_id' => $inventoryItem->id ?? null,
                'order_id' => $wasteData['waste_type'] === 'returned' ? $orders->random()->id : null,
                'waste_type' => $wasteData['waste_type'],
                'waste_source' => $wasteData['waste_source'],
                'item_name' => $wasteData['item_name'],
                'item_description' => 'Production waste from daily operations',
                'quantity_wasted' => $wasteData['quantity_wasted'],
                'unit_of_measure' => in_array($wasteData['waste_type'], ['expired', 'damaged']) && 
                                   str_contains(strtolower($wasteData['item_name']), 'flour') ? 'kg' : 'piece',
                'unit_cost' => $wasteData['unit_cost'],
                'total_waste_cost' => $wasteData['quantity_wasted'] * $wasteData['unit_cost'],
                'currency' => 'JOD',
                'expiry_date' => $wasteData['waste_type'] === 'expired' ? $wasteData['waste_date'] : null,
                'production_date' => now()->subDays(rand(1, 10))->format('Y-m-d'),
                'waste_date' => $wasteData['waste_date'],
                'waste_reason' => $wasteData['waste_reason'],
                'prevention_notes' => $wasteData['prevention_notes'],
                'disposal_method' => $wasteData['disposal_method'],
                'disposal_notes' => match($wasteData['disposal_method']) {
                    'compost' => 'Sent to organic waste facility for composting',
                    'donation' => 'Donated to local charity organization',
                    'landfill' => 'Disposed as general waste following regulations',
                    'recycling' => 'Processed through recycling program',
                    default => 'Standard disposal procedure followed'
                },
                'photos' => json_encode([
                    'waste_photo_' . ($index + 1) . '_1.jpg',
                    'waste_photo_' . ($index + 1) . '_2.jpg'
                ]),
                'reported_by' => $reportedBy->id,
                'approved_by' => $approvedBy->id,
                'approved_at' => now()->subDays(rand(0, 3)),
                'status' => 'approved'
            ]);
        }

        $totalWasteCost = WasteLog::sum('total_waste_cost');
        $this->command->info('✅ Created ' . count($wasteLogsData) . ' waste log entries');
        $this->command->info('   • Total waste cost: ~' . number_format($totalWasteCost, 2) . ' JOD');
        $this->command->info('   • Waste types: Expired, Damaged, Overproduction, Quality Control, Returns');
        $this->command->info('   • Disposal methods: Compost, Donation, Landfill, Recycling');
    }
}