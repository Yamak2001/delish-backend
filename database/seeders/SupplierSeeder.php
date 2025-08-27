<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\User;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@delish.com',
                'password' => bcrypt('password'),
            ]);
        }

        $suppliersData = [
            [
                'supplier_name' => 'Al-Mashreq Flour Mill',
                'contact_person' => 'Ahmad Khalil',
                'email' => 'ahmad@mashreqflour.com',
                'phone' => '+962-6-555-0101',
                'address' => 'Industrial Area, Zarqa',
                'city' => 'Zarqa',
                'country' => 'Jordan',
                'postal_code' => '13110',
                'tax_number' => 'JO123456789',
                'supplier_type' => 'ingredient',
                'status' => 'active',
                'payment_terms' => 'net_30',
                'credit_limit' => 25000.00,
                'currency' => 'JOD',
                'lead_time_days' => 3,
                'rating' => 4.8,
                'notes' => 'Primary flour supplier - excellent quality and reliability',
                'created_by' => $user->id,
            ],
            [
                'supplier_name' => 'Jordan Dairy Products Co.',
                'contact_person' => 'Fatima Al-Zahra',
                'email' => 'fatima@jordandairy.jo',
                'phone' => '+962-6-555-0102',
                'address' => 'Amman Industrial City',
                'city' => 'Amman',
                'country' => 'Jordan',
                'postal_code' => '11831',
                'tax_number' => 'JO987654321',
                'supplier_type' => 'ingredient',
                'status' => 'active',
                'payment_terms' => 'net_15',
                'credit_limit' => 15000.00,
                'currency' => 'JOD',
                'lead_time_days' => 2,
                'rating' => 4.6,
                'notes' => 'Fresh dairy products - butter, cream, milk',
                'created_by' => $user->id,
            ],
            [
                'supplier_name' => 'Sweet Ingredients Trading',
                'contact_person' => 'Omar Mahmoud',
                'email' => 'omar@sweetingredients.com',
                'phone' => '+962-6-555-0103',
                'address' => 'King Abdullah Street',
                'city' => 'Irbid',
                'country' => 'Jordan',
                'postal_code' => '21110',
                'tax_number' => 'JO456789123',
                'supplier_type' => 'ingredient',
                'status' => 'active',
                'payment_terms' => 'net_30',
                'credit_limit' => 20000.00,
                'currency' => 'JOD',
                'lead_time_days' => 5,
                'rating' => 4.3,
                'notes' => 'Sugar, vanilla, chocolate, spices, and flavorings',
                'created_by' => $user->id,
            ],
            [
                'supplier_name' => 'Premium Packaging Solutions',
                'contact_person' => 'Layla Hassan',
                'email' => 'layla@premiumpack.jo',
                'phone' => '+962-6-555-0104',
                'address' => 'Free Trade Zone',
                'city' => 'Aqaba',
                'country' => 'Jordan',
                'postal_code' => '77110',
                'tax_number' => 'JO789123456',
                'supplier_type' => 'packaging',
                'status' => 'active',
                'payment_terms' => 'net_45',
                'credit_limit' => 10000.00,
                'currency' => 'JOD',
                'lead_time_days' => 7,
                'rating' => 4.5,
                'notes' => 'Cake boxes, bags, labels, and custom packaging',
                'created_by' => $user->id,
            ],
            [
                'supplier_name' => 'Kitchen Equipment Pro',
                'contact_person' => 'Khaled Mansour',
                'email' => 'khaled@kitchenpro.com',
                'phone' => '+962-6-555-0105',
                'address' => 'Commercial District',
                'city' => 'Amman',
                'country' => 'Jordan',
                'postal_code' => '11195',
                'tax_number' => 'JO159753486',
                'supplier_type' => 'equipment',
                'status' => 'active',
                'payment_terms' => 'net_60',
                'credit_limit' => 50000.00,
                'currency' => 'JOD',
                'lead_time_days' => 14,
                'rating' => 4.2,
                'notes' => 'Commercial ovens, mixers, and baking equipment',
                'created_by' => $user->id,
            ],
            [
                'supplier_name' => 'Quality Control Services',
                'contact_person' => 'Dr. Rania Khoury',
                'email' => 'rania@qualitycontrol.jo',
                'phone' => '+962-6-555-0106',
                'address' => 'Science Park',
                'city' => 'Amman',
                'country' => 'Jordan',
                'postal_code' => '11942',
                'tax_number' => 'JO357159468',
                'supplier_type' => 'service',
                'status' => 'active',
                'payment_terms' => 'net_30',
                'credit_limit' => 5000.00,
                'currency' => 'JOD',
                'lead_time_days' => 1,
                'rating' => 5.0,
                'notes' => 'Food safety testing and quality assurance services',
                'created_by' => $user->id,
            ]
        ];

        foreach ($suppliersData as $supplierData) {
            Supplier::create($supplierData);
        }

        echo "Supplier seeder completed successfully!\n";
        echo "Created 6 suppliers with different types and statuses.\n";
        echo "All suppliers are active and ready for purchase orders.\n";
    }
}