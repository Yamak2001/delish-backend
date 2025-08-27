<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\User;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $suppliers = Supplier::all();
        $inventoryItems = InventoryItem::all();

        if (!$admin || $suppliers->isEmpty() || $inventoryItems->isEmpty()) {
            $this->command->warn('Skipping PurchaseOrderSeeder - missing dependencies');
            return;
        }

        $purchaseOrdersData = [
            [
                'supplier' => $suppliers->where('company_name', 'Al-Mashreq Flour Mill')->first(),
                'status' => 'received',
                'expected_delivery_date' => '2025-08-20',
                'actual_delivery_date' => '2025-08-19',
                'items' => [
                    ['name' => 'Premium Wheat Flour', 'quantity' => 500, 'unit_price' => 1.45],
                    ['name' => 'All-Purpose Flour', 'quantity' => 300, 'unit_price' => 1.30]
                ]
            ],
            [
                'supplier' => $suppliers->where('company_name', 'Jordan Dairy Products Co.')->first(),
                'status' => 'confirmed',
                'expected_delivery_date' => '2025-08-28',
                'items' => [
                    ['name' => 'Fresh Butter', 'quantity' => 50, 'unit_price' => 4.50],
                    ['name' => 'Heavy Cream', 'quantity' => 100, 'unit_price' => 2.80],
                    ['name' => 'Whole Milk', 'quantity' => 200, 'unit_price' => 1.20]
                ]
            ],
            [
                'supplier' => $suppliers->where('company_name', 'Sweet Ingredients Trading')->first(),
                'status' => 'sent',
                'expected_delivery_date' => '2025-08-30',
                'items' => [
                    ['name' => 'Granulated Sugar', 'quantity' => 250, 'unit_price' => 1.95],
                    ['name' => 'Brown Sugar', 'quantity' => 100, 'unit_price' => 2.10],
                    ['name' => 'Vanilla Extract', 'quantity' => 20, 'unit_price' => 12.50],
                    ['name' => 'Cocoa Powder', 'quantity' => 75, 'unit_price' => 8.25]
                ]
            ],
            [
                'supplier' => $suppliers->where('company_name', 'Premium Packaging Solutions')->first(),
                'status' => 'draft',
                'expected_delivery_date' => '2025-09-05',
                'items' => [
                    ['name' => 'Cake Boxes (Large)', 'quantity' => 500, 'unit_price' => 0.75],
                    ['name' => 'Cake Boxes (Medium)', 'quantity' => 300, 'unit_price' => 0.55],
                    ['name' => 'Cupcake Containers', 'quantity' => 1000, 'unit_price' => 0.25],
                    ['name' => 'Food Labels', 'quantity' => 2000, 'unit_price' => 0.05]
                ]
            ],
            [
                'supplier' => $suppliers->where('company_name', 'Kitchen Equipment Pro')->first(),
                'status' => 'confirmed',
                'expected_delivery_date' => '2025-09-15',
                'items' => [
                    ['name' => 'Commercial Mixer Bowls', 'quantity' => 3, 'unit_price' => 85.00],
                    ['name' => 'Baking Trays Set', 'quantity' => 10, 'unit_price' => 25.00],
                    ['name' => 'Measuring Cups Set', 'quantity' => 5, 'unit_price' => 15.00]
                ]
            ]
        ];

        foreach ($purchaseOrdersData as $index => $data) {
            if (!$data['supplier']) continue;

            $po = PurchaseOrder::create([
                'po_number' => 'PO-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $data['supplier']->id,
                'order_date' => now()->subDays(rand(1, 15)),
                'expected_delivery_date' => $data['expected_delivery_date'],
                'actual_delivery_date' => $data['actual_delivery_date'] ?? null,
                'status' => $data['status'],
                'currency' => 'JOD',
                'payment_terms' => $data['supplier']->payment_terms ?? 'Net 30',
                'notes' => 'Standard order for production requirements',
                'created_by' => $admin->id,
                'approved_by' => $data['status'] !== 'draft' ? $admin->id : null,
                'approved_at' => $data['status'] !== 'draft' ? now()->subDays(rand(1, 10)) : null,
            ]);

            $subtotal = 0;
            foreach ($data['items'] as $itemData) {
                $inventoryItem = $inventoryItems->where('name', 'LIKE', '%' . explode(' ', $itemData['name'])[0] . '%')->first();
                
                $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                $subtotal += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $inventoryItem->id ?? null,
                    'item_name' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $lineTotal,
                    'received_quantity' => $data['status'] === 'received' ? $itemData['quantity'] : 0,
                    'status' => $data['status'] === 'received' ? 'received' : 'pending',
                    'notes' => 'Quality checked and approved'
                ]);
            }

            $taxAmount = $subtotal * 0.16; // 16% VAT
            $totalAmount = $subtotal + $taxAmount;

            $po->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount
            ]);
        }

        $this->command->info('✅ Created ' . count($purchaseOrdersData) . ' purchase orders with items');
        $this->command->info('   • Status distribution: Draft(1), Sent(1), Confirmed(2), Received(1)');
        $this->command->info('   • Total value: ~' . PurchaseOrder::sum('total_amount') . ' JOD');
    }
}