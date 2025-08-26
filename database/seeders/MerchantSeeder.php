<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Merchant;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = [
            [
                'business_name' => 'Sweet Dreams Cafe',
                'contact_person_name' => 'Sarah Johnson',
                'whatsapp_business_phone' => '+1234567801',
                'contact_email' => 'sarah@sweetdreamscafe.com',
                'location_address' => '123 Main St, Downtown, New York, NY 10001',
                'contact_phone' => '+1234567801',
                'credit_limit' => 5000.00,
                'payment_terms' => 'net_30',
                'account_status' => 'active',
                'notes' => 'Cafe specializing in artisanal desserts, established customer',
            ],
            [
                'business_name' => 'Deluxe Desserts Restaurant',
                'contact_person_name' => 'Michael Chen',
                'whatsapp_business_phone' => '+1234567802',
                'contact_email' => 'michael@deluxedesserts.com',
                'location_address' => '456 Oak Ave, Midtown, New York, NY 10002',
                'contact_phone' => '+1234567802',
                'credit_limit' => 10000.00,
                'payment_terms' => 'net_15',
                'account_status' => 'active',
                'notes' => 'High-volume restaurant client, premium tier',
            ],
            [
                'business_name' => 'Corner Bakery',
                'contact_person_name' => 'Lisa Rodriguez',
                'whatsapp_business_phone' => '+1234567803',
                'contact_email' => 'lisa@cornerbakery.com',
                'location_address' => '789 Pine St, Brooklyn, NY 11201',
                'contact_phone' => '+1234567803',
                'credit_limit' => 3000.00,
                'payment_terms' => 'net_30',
                'account_status' => 'active',
                'notes' => 'Local bakery, regular weekly orders',
            ],
            [
                'business_name' => 'Gourmet Events Catering',
                'contact_person_name' => 'David Wilson',
                'whatsapp_business_phone' => '+1234567804',
                'contact_email' => 'david@gourmeteventscatering.com',
                'location_address' => '321 Cedar Blvd, Queens, NY 11435',
                'contact_phone' => '+1234567804',
                'credit_limit' => 15000.00,
                'payment_terms' => 'net_15',
                'account_status' => 'active',
                'notes' => 'Premium catering client, large event orders',
            ],
            [
                'business_name' => 'The Cupcake Shop',
                'contact_person_name' => 'Emma Thompson',
                'whatsapp_business_phone' => '+1234567805',
                'contact_email' => 'emma@thecupcakeshop.com',
                'location_address' => '654 Maple Dr, Staten Island, NY 10314',
                'contact_phone' => '+1234567805',
                'credit_limit' => 2500.00,
                'payment_terms' => 'net_30',
                'account_status' => 'active',
                'notes' => 'Retail cupcake specialty shop',
            ],
            [
                'business_name' => 'Hotel Plaza Restaurant',
                'contact_person_name' => 'James Martinez',
                'whatsapp_business_phone' => '+1234567806',
                'contact_email' => 'james@hotelplaza.com',
                'location_address' => '987 Broadway, Manhattan, NY 10019',
                'contact_phone' => '+1234567806',
                'credit_limit' => 20000.00,
                'payment_terms' => 'net_15',
                'account_status' => 'active',
                'notes' => 'Hotel restaurant, high-volume premium client',
            ],
            [
                'business_name' => 'Test Inactive Merchant',
                'contact_person_name' => 'Inactive User',
                'whatsapp_business_phone' => '+1234567899',
                'contact_email' => 'inactive@test.com',
                'location_address' => '000 Test St, Test City, NY 00000',
                'contact_phone' => '+1234567899',
                'credit_limit' => 1000.00,
                'payment_terms' => 'net_30',
                'account_status' => 'inactive',
                'notes' => 'Test inactive merchant for system validation',
            ]
        ];

        foreach ($merchants as $merchantData) {
            Merchant::create($merchantData);
        }

        $this->command->info('Created ' . count($merchants) . ' test merchants');
    }
}