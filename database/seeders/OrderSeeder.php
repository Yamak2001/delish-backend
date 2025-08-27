<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\User;
use App\Models\Merchant;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $recipes = Recipe::all();
        $merchants = Merchant::all();

        if (!$admin || $recipes->isEmpty()) {
            $this->command->warn('Skipping OrderSeeder - missing dependencies');
            return;
        }

        $ordersData = [
            [
                'customer_name' => 'Sweet Dreams Cafe',
                'customer_email' => 'orders@sweetdreamscafe.com',
                'customer_phone' => '+962-6-555-2001',
                'delivery_address' => 'Rainbow Street 45, Amman, Jordan',
                'delivery_date' => '2025-08-27',
                'status' => 'delivered',
                'items' => [
                    ['recipe' => 'Chocolate Cake', 'quantity' => 3, 'unit_price' => 28.00],
                    ['recipe' => 'Vanilla Cupcakes', 'quantity' => 24, 'unit_price' => 2.50]
                ],
                'notes' => 'Regular customer - premium packaging'
            ],
            [
                'customer_name' => 'Corner Bakery',
                'customer_email' => 'supply@cornerbakery.jo',
                'customer_phone' => '+962-6-555-2002', 
                'delivery_address' => 'Downtown Circle, Salt, Jordan',
                'delivery_date' => '2025-08-28',
                'status' => 'ready',
                'items' => [
                    ['recipe' => 'Cookies', 'quantity' => 50, 'unit_price' => 1.75],
                    ['recipe' => 'Muffins', 'quantity' => 30, 'unit_price' => 2.25],
                    ['recipe' => 'Brownies', 'quantity' => 20, 'unit_price' => 3.00]
                ],
                'notes' => 'Weekly bulk order - standard delivery'
            ],
            [
                'customer_name' => 'Deluxe Events Catering',
                'customer_email' => 'events@deluxecatering.com',
                'customer_phone' => '+962-6-555-2003',
                'delivery_address' => 'Wedding Hall District, Amman, Jordan',
                'delivery_date' => '2025-08-29',
                'status' => 'in_production',
                'items' => [
                    ['recipe' => 'Wedding Cake', 'quantity' => 1, 'unit_price' => 150.00],
                    ['recipe' => 'Mini Cheesecakes', 'quantity' => 100, 'unit_price' => 4.50],
                    ['recipe' => 'Chocolate Truffles', 'quantity' => 200, 'unit_price' => 1.25]
                ],
                'notes' => 'VIP event - special decoration required'
            ],
            [
                'customer_name' => 'The Cupcake Shop',
                'customer_email' => 'orders@cupcakeshop.jo',
                'customer_phone' => '+962-6-555-2004',
                'delivery_address' => 'Mecca Street 123, Amman, Jordan',
                'delivery_date' => '2025-08-30',
                'status' => 'confirmed',
                'items' => [
                    ['recipe' => 'Red Velvet Cupcakes', 'quantity' => 36, 'unit_price' => 3.00],
                    ['recipe' => 'Lemon Bars', 'quantity' => 24, 'unit_price' => 2.75]
                ],
                'notes' => 'Franchise partner - discounted pricing'
            ],
            [
                'customer_name' => 'Hotel Plaza Restaurant',
                'customer_email' => 'pastry@hotelplaza.com',
                'customer_phone' => '+962-6-555-2005',
                'delivery_address' => 'Hotel Plaza, King Hussein Street, Amman',
                'delivery_date' => '2025-09-01',
                'status' => 'pending',
                'items' => [
                    ['recipe' => 'Tiramisu', 'quantity' => 12, 'unit_price' => 8.50],
                    ['recipe' => 'Chocolate Mousse', 'quantity' => 24, 'unit_price' => 6.25],
                    ['recipe' => 'Fruit Tarts', 'quantity' => 18, 'unit_price' => 5.75]
                ],
                'notes' => 'Hotel contract - monthly recurring order'
            ],
            [
                'customer_name' => 'Birthday Party Special',
                'customer_email' => 'sarah.ahmad@gmail.com',
                'customer_phone' => '+962-7-9999-1234',
                'delivery_address' => 'Jubeiha Area, House 45, Amman',
                'delivery_date' => '2025-09-02',
                'status' => 'pending',
                'items' => [
                    ['recipe' => 'Birthday Cake', 'quantity' => 1, 'unit_price' => 35.00],
                    ['recipe' => 'Party Cupcakes', 'quantity' => 20, 'unit_price' => 2.80]
                ],
                'notes' => 'Custom message: "Happy 8th Birthday Omar!" - Blue theme'
            ]
        ];

        foreach ($ordersData as $index => $orderData) {
            $merchant = $merchants->where('business_name', $orderData['customer_name'])->first();
            
            // Convert items to JSON format expected by the table
            $orderItems = [];
            foreach ($orderData['items'] as $itemData) {
                $orderItems[] = [
                    'recipe_name' => $itemData['recipe'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'special_notes' => $itemData['notes'] ?? null
                ];
            }

            $order = Order::create([
                'merchant_id' => $merchant->id ?? 1, // Default to first merchant if not found
                'whatsapp_order_id' => 'WA-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'order_items' => $orderItems,
                'total_amount' => collect($orderItems)->sum('total_price'),
                'order_date' => now()->subDays(rand(1, 7)),
                'requested_delivery_date' => $orderData['delivery_date'],
                'order_status' => match($orderData['status']) {
                    'delivered', 'ready', 'in_production' => 'confirmed',
                    'pending' => 'pending',
                    default => 'pending'
                },
                'special_notes' => $orderData['notes'],
                'delivery_address' => $orderData['delivery_address'],
                'assigned_workflow_id' => null,
                'payment_terms_override' => null,
            ]);
        }

        $this->command->info('✅ Created ' . count($ordersData) . ' customer orders with items');
        $this->command->info('   • Status distribution: Pending(2), Confirmed(1), In Production(1), Ready(1), Delivered(1)');
        $this->command->info('   • Total value: ~' . Order::sum('total_amount') . ' JOD');
    }
}