<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $drivers = User::where('role', 'driver')->get();
        $orders = Order::all();

        if ($drivers->isEmpty() || $orders->isEmpty()) {
            $this->command->warn('Skipping DeliverySeeder - missing drivers or orders');
            return;
        }

        // Create drivers if none exist
        if ($drivers->isEmpty()) {
            $driversData = [
                ['name' => 'Ahmad Al-Driver', 'email' => 'ahmad.driver@delish.com', 'phone' => '+962-7-1111-0001'],
                ['name' => 'Omar Delivery', 'email' => 'omar.delivery@delish.com', 'phone' => '+962-7-1111-0002'],
                ['name' => 'Khaled Transport', 'email' => 'khaled.transport@delish.com', 'phone' => '+962-7-1111-0003']
            ];

            foreach ($driversData as $driverData) {
                $driver = User::create([
                    'name' => $driverData['name'],
                    'email' => $driverData['email'],
                    'password' => bcrypt('driver123'),
                    'role' => 'driver',
                    'department' => 'delivery',
                    'phone_number' => $driverData['phone'],
                    'status' => 'active'
                ]);
                $drivers->push($driver);
            }
        }

        $deliveriesData = [
            [
                'order' => $orders->first(),
                'driver' => $drivers->first(),
                'vehicle_info' => 'Toyota Hiace - Plate: 12345 AMN',
                'status' => 'delivered',
                'scheduled_pickup_time' => '2025-08-26T08:00:00Z',
                'actual_pickup_time' => '2025-08-26T08:15:00Z',
                'scheduled_delivery_time' => '2025-08-26T09:30:00Z',
                'actual_delivery_time' => '2025-08-26T09:45:00Z',
                'pickup_coords' => ['lat' => 31.9539, 'lng' => 35.9106], // Delish Factory
                'delivery_coords' => ['lat' => 31.9456, 'lng' => 35.9284], // Rainbow Street
                'current_coords' => ['lat' => 31.9456, 'lng' => 35.9284], // At destination
                'distance_km' => 5.2,
                'delivered_to' => 'Cafe Manager',
                'delivery_notes' => 'Delivered successfully to main entrance'
            ],
            [
                'order' => $orders->skip(1)->first(),
                'driver' => $drivers->get(1) ?? $drivers->first(),
                'vehicle_info' => 'Hyundai Porter - Plate: 67890 SLT',
                'status' => 'in_transit',
                'scheduled_pickup_time' => '2025-08-27T10:00:00Z',
                'actual_pickup_time' => '2025-08-27T10:10:00Z',
                'scheduled_delivery_time' => '2025-08-27T12:00:00Z',
                'pickup_coords' => ['lat' => 31.9539, 'lng' => 35.9106],
                'delivery_coords' => ['lat' => 32.0347, 'lng' => 35.8247], // Salt
                'current_coords' => ['lat' => 32.0100, 'lng' => 35.8500], // En route
                'distance_km' => 28.5,
                'delivery_instructions' => 'Call before arrival - loading dock entrance'
            ],
            [
                'order' => $orders->skip(2)->first(),
                'driver' => $drivers->get(2) ?? $drivers->first(),
                'vehicle_info' => 'Mercedes Sprinter - Plate: EVENT01',
                'status' => 'scheduled',
                'scheduled_pickup_time' => '2025-08-29T06:00:00Z',
                'scheduled_delivery_time' => '2025-08-29T07:30:00Z',
                'pickup_coords' => ['lat' => 31.9539, 'lng' => 35.9106],
                'delivery_coords' => ['lat' => 31.9615, 'lng' => 35.9055], // Wedding Hall District
                'distance_km' => 3.8,
                'delivery_instructions' => 'VIP delivery - handle with extreme care. Contact event coordinator upon arrival.'
            ],
            [
                'order' => $orders->skip(3)->first(),
                'driver' => $drivers->first(),
                'vehicle_info' => 'Toyota Hiace - Plate: 12345 AMN',
                'status' => 'picked_up',
                'scheduled_pickup_time' => '2025-08-30T11:00:00Z',
                'actual_pickup_time' => '2025-08-30T11:05:00Z',
                'scheduled_delivery_time' => '2025-08-30T12:15:00Z',
                'pickup_coords' => ['lat' => 31.9539, 'lng' => 35.9106],
                'delivery_coords' => ['lat' => 31.9394, 'lng' => 35.9349], // Mecca Street
                'current_coords' => ['lat' => 31.9500, 'lng' => 35.9200], // En route
                'distance_km' => 4.7,
                'delivery_instructions' => 'Franchise partner - use back entrance'
            ],
            [
                'order' => $orders->skip(4)->first(),
                'driver' => $drivers->get(1) ?? $drivers->first(),
                'vehicle_info' => 'Hyundai Porter - Plate: 67890 SLT',
                'status' => 'scheduled',
                'scheduled_pickup_time' => '2025-09-01T14:00:00Z',
                'scheduled_delivery_time' => '2025-09-01T15:00:00Z',
                'pickup_coords' => ['lat' => 31.9539, 'lng' => 35.9106],
                'delivery_coords' => ['lat' => 31.9515, 'lng' => 35.9239], // King Hussein Street
                'distance_km' => 2.9,
                'delivery_instructions' => 'Hotel contract delivery - service entrance, ask for pastry chef'
            ]
        ];

        foreach ($deliveriesData as $index => $deliveryData) {
            if (!$deliveryData['order']) continue;

            $delivery = Delivery::create([
                'delivery_number' => 'DEL-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'order_id' => $deliveryData['order']->id,
                'driver_id' => $deliveryData['driver']->id,
                'vehicle_info' => $deliveryData['vehicle_info'],
                'status' => $deliveryData['status'],
                'scheduled_pickup_time' => $deliveryData['scheduled_pickup_time'],
                'actual_pickup_time' => $deliveryData['actual_pickup_time'] ?? null,
                'scheduled_delivery_time' => $deliveryData['scheduled_delivery_time'],
                'estimated_delivery_time' => $deliveryData['scheduled_delivery_time'],
                'actual_delivery_time' => $deliveryData['actual_delivery_time'] ?? null,
                'pickup_address' => 'Delish Factory, Industrial Area, Amman',
                'delivery_address' => $deliveryData['order']->delivery_address,
                'pickup_latitude' => $deliveryData['pickup_coords']['lat'],
                'pickup_longitude' => $deliveryData['pickup_coords']['lng'],
                'delivery_latitude' => $deliveryData['delivery_coords']['lat'],
                'delivery_longitude' => $deliveryData['delivery_coords']['lng'],
                'current_latitude' => $deliveryData['current_coords']['lat'] ?? null,
                'current_longitude' => $deliveryData['current_coords']['lng'] ?? null,
                'distance_km' => $deliveryData['distance_km'],
                'delivery_instructions' => $deliveryData['delivery_instructions'] ?? null,
                'delivered_to' => $deliveryData['delivered_to'] ?? null,
                'delivery_notes' => $deliveryData['delivery_notes'] ?? null,
                'proof_of_delivery' => $deliveryData['status'] === 'delivered' ? 'signature_photo_' . ($index + 1) . '.jpg' : null
            ]);

            // Create delivery items from order items JSON
            foreach ($deliveryData['order']->order_items as $orderItem) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'order_item_id' => null, // No separate order items table
                    'item_name' => $orderItem['recipe_name'],
                    'quantity' => $orderItem['quantity'],
                    'delivered_quantity' => $deliveryData['status'] === 'delivered' ? $orderItem['quantity'] : 0,
                    'condition' => 'good',
                    'notes' => 'Standard packaging'
                ]);
            }
        }

        $this->command->info('✅ Created ' . count($deliveriesData) . ' deliveries with GPS tracking');
        $this->command->info('   • Status: Delivered(1), In Transit(1), Picked Up(1), Scheduled(2)');
        $this->command->info('   • Total distance: ~' . collect($deliveriesData)->sum('distance_km') . ' km');
        $this->command->info('   • Created ' . $drivers->count() . ' delivery drivers');
    }
}