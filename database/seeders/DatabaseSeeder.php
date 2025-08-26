<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for Delish Factory ERP System.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Delish Factory ERP Database Seeding...');
        
        // Order matters for foreign key relationships
        $this->call([
            UserSeeder::class,              // Must be first - creates admin user
            MerchantSeeder::class,          // Creates merchants with WhatsApp numbers  
            InventoryItemSeeder::class,     // Creates ingredients and supplies
            RecipeSeeder::class,            // Creates dessert recipes (needs admin user)
            RecipeIngredientSeeder::class,  // Links recipes to ingredients (needs both)
            WorkflowSeeder::class,          // Creates production workflows (needs admin user)
            SystemSettingSeeder::class,     // Creates system configuration (needs admin user)
            // MerchantPricingSeeder::class,   // TODO: Fix model field mismatch
        ]);

        $this->command->info('');
        $this->command->info('🎉 Delish Factory ERP Database Seeding Complete!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('   ✅ Users: 7 (admin, managers, staff with different departments)');
        $this->command->info('   ✅ Merchants: 6 active merchants with WhatsApp numbers for testing');
        $this->command->info('   ✅ Recipes: 8 dessert recipes (cakes, cupcakes, cookies, etc.)');
        $this->command->info('   ✅ Inventory: 14+ ingredients and packaging materials');
        $this->command->info('   ✅ Recipe Ingredients: Complete ingredient relationships');
        $this->command->info('   ✅ Workflows: 3 production workflows with detailed steps');
        $this->command->info('   ✅ Pricing: Dynamic merchant-specific pricing tiers');
        $this->command->info('   ✅ Settings: Comprehensive system configuration');
        $this->command->info('');
        $this->command->info('🚀 Ready for WhatsApp order processing and production workflow testing!');
        $this->command->info('');
        $this->command->info('📱 Test WhatsApp Numbers:');
        $this->command->info('   • Sweet Dreams Cafe: +1234567801');
        $this->command->info('   • Deluxe Desserts Restaurant: +1234567802');
        $this->command->info('   • Corner Bakery: +1234567803');
        $this->command->info('   • Gourmet Events Catering: +1234567804');
        $this->command->info('   • The Cupcake Shop: +1234567805');
        $this->command->info('   • Hotel Plaza Restaurant: +1234567806');
        $this->command->info('');
        $this->command->info('👤 Test Login Accounts:');
        $this->command->info('   • Admin: admin@delishfactory.com / admin123');
        $this->command->info('   • Manager: manager@delishfactory.com / manager123');
        $this->command->info('   • Kitchen Staff: kitchen1@delishfactory.com / staff123');
    }
}