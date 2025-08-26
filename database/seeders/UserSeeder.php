<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@delishfactory.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'department' => 'management',
                'phone_number' => '+1234567890',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Production Manager',
                'email' => 'manager@delishfactory.com',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'department' => 'production',
                'phone_number' => '+1234567891',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Kitchen Staff 1',
                'email' => 'kitchen1@delishfactory.com',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'department' => 'kitchen',
                'phone_number' => '+1234567892',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Kitchen Staff 2',
                'email' => 'kitchen2@delishfactory.com',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'department' => 'kitchen',
                'phone_number' => '+1234567893',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Quality Control',
                'email' => 'qc@delishfactory.com',
                'password' => Hash::make('qc123'),
                'role' => 'staff',
                'department' => 'quality_control',
                'phone_number' => '+1234567894',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Packaging Staff',
                'email' => 'packaging@delishfactory.com',
                'password' => Hash::make('pack123'),
                'role' => 'staff',
                'department' => 'packaging',
                'phone_number' => '+1234567895',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Inventory Manager',
                'email' => 'inventory@delishfactory.com',
                'password' => Hash::make('inv123'),
                'role' => 'manager',
                'department' => 'inventory',
                'phone_number' => '+1234567896',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Created ' . count($users) . ' test users');
    }
}