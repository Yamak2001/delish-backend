<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('role', 'admin')->first();

        $settings = [
            // Business Configuration
            [
                'setting_key' => 'company_name',
                'setting_value' => 'Delish Factory Management System',
                'setting_type' => 'string',
                'description' => 'Company name displayed in system and invoices',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'company_address',
                'setting_value' => '123 Sweet Street, Dessert District, NY 10001',
                'setting_type' => 'string',
                'description' => 'Company address for invoices and contact information',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'company_phone',
                'setting_value' => '+1-555-DELISH-1',
                'setting_type' => 'string',
                'description' => 'Main company phone number',
                'updated_by_user_id' => $adminUser->id,
            ],

            // WhatsApp Integration Settings
            [
                'setting_key' => 'whatsapp_webhook_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable WhatsApp webhook for order processing',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'whatsapp_verify_token',
                'setting_value' => 'delish_webhook_verify_2025',
                'setting_type' => 'string',
                'description' => 'WhatsApp webhook verification token',
                'updated_by_user_id' => $adminUser->id,
            ],

            // Order Processing Settings
            [
                'setting_key' => 'minimum_order_value',
                'setting_value' => '25.00',
                'setting_type' => 'number',
                'description' => 'Minimum order value for processing',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'default_order_lead_time_hours',
                'setting_value' => '24',
                'setting_type' => 'number',
                'description' => 'Default lead time for order processing in hours',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'auto_create_job_tickets',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Automatically create job tickets when orders are confirmed',
                'updated_by_user_id' => $adminUser->id,
            ],

            // Inventory Management Settings
            [
                'setting_key' => 'low_stock_notification_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable notifications when inventory items reach minimum stock level',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'inventory_valuation_method',
                'setting_value' => 'FIFO',
                'setting_type' => 'string',
                'description' => 'Inventory valuation method (FIFO, LIFO, Average)',
                'updated_by_user_id' => $adminUser->id,
            ],

            // Pricing and Financial Settings
            [
                'setting_key' => 'default_markup_percentage',
                'setting_value' => '150.0',
                'setting_type' => 'number',
                'description' => 'Default markup percentage for new recipes',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'tax_rate_percentage',
                'setting_value' => '8.25',
                'setting_type' => 'number',
                'description' => 'Default tax rate percentage for invoicing',
                'updated_by_user_id' => $adminUser->id,
            ],

            // Production Settings
            [
                'setting_key' => 'production_capacity_daily_orders',
                'setting_value' => '50',
                'setting_type' => 'number',
                'description' => 'Maximum daily order processing capacity',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'quality_control_required',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Require quality control sign-off for all orders',
                'updated_by_user_id' => $adminUser->id,
            ],

            // Waste Management Settings
            [
                'setting_key' => 'waste_collection_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable automated waste collection scheduling',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'waste_prevention_fifo_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable FIFO waste prevention logic for reorders',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'waste_cost_per_pickup',
                'setting_value' => '75.00',
                'setting_type' => 'number',
                'description' => 'Cost per waste collection pickup',
                'updated_by_user_id' => $adminUser->id,
            ],

            // System Settings
            [
                'setting_key' => 'audit_log_retention_days',
                'setting_value' => '365',
                'setting_type' => 'number',
                'description' => 'Number of days to retain audit log entries',
                'updated_by_user_id' => $adminUser->id,
            ],
            [
                'setting_key' => 'system_maintenance_mode',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'description' => 'Enable system maintenance mode (blocks normal operations)',
                'updated_by_user_id' => $adminUser->id,
            ],
        ];

        foreach ($settings as $settingData) {
            SystemSetting::create($settingData);
        }

        $this->command->info('Created ' . count($settings) . ' system settings');
    }
}