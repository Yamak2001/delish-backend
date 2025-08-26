<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\User;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('role', 'admin')->first();

        $recipes = [
            [
                'recipe_name' => 'Classic Chocolate Cake',
                'description' => 'Rich, moist chocolate cake with dark chocolate ganache',
                'preparation_time_minutes' => 120,
                'serving_size' => 12,
                'cost_per_unit' => 18.50,
                'active_status' => true,
                'special_instructions' => 'Ensure chocolate is properly tempered for ganache',
                'storage_requirements' => 'Refrigerate after assembly, serve at room temperature',
                'shelf_life_days' => 3,
            ],
            [
                'recipe_name' => 'Vanilla Bean Cupcakes',
                'description' => 'Light and fluffy vanilla cupcakes with buttercream frosting',
                'preparation_time_minutes' => 90,
                'serving_size' => 24,
                'cost_per_unit' => 12.75,
                'active_status' => true,
                'special_instructions' => 'Do not overmix batter, use real vanilla beans for best flavor',
                'storage_requirements' => 'Store covered at room temperature',
                'shelf_life_days' => 2,
            ],
            [
                'recipe_name' => 'Strawberry Cheesecake',
                'description' => 'Creamy New York style cheesecake with fresh strawberry topping',
                'preparation_time_minutes' => 480,
                'serving_size' => 16,
                'cost_per_unit' => 22.30,
                'active_status' => true,
                'special_instructions' => 'Requires water bath baking, must chill overnight',
                'storage_requirements' => 'Must be refrigerated at all times',
                'shelf_life_days' => 4,
            ],
            [
                'recipe_name' => 'Chocolate Chip Cookies',
                'description' => 'Classic chewy chocolate chip cookies with premium chocolate',
                'preparation_time_minutes' => 45,
                'serving_size' => 48,
                'cost_per_unit' => 8.90,
                'active_status' => true,
                'special_instructions' => 'Chill dough for at least 30 minutes before baking',
                'storage_requirements' => 'Store in airtight container at room temperature',
                'shelf_life_days' => 7,
            ],
            [
                'recipe_name' => 'Lemon Bars',
                'description' => 'Tangy lemon curd on buttery shortbread crust',
                'preparation_time_minutes' => 180,
                'serving_size' => 24,
                'cost_per_unit' => 10.25,
                'active_status' => true,
                'special_instructions' => 'Use fresh lemon juice only, dust with powdered sugar before serving',
                'storage_requirements' => 'Refrigerate, bring to room temperature before serving',
                'shelf_life_days' => 3,
            ],
            [
                'recipe_name' => 'Red Velvet Cake',
                'description' => 'Classic red velvet cake with cream cheese frosting',
                'preparation_time_minutes' => 150,
                'serving_size' => 12,
                'cost_per_unit' => 16.80,
                'active_status' => true,
                'special_instructions' => 'Add food coloring gradually, do not overmix',
                'storage_requirements' => 'Refrigerate due to cream cheese frosting',
                'shelf_life_days' => 3,
            ],
            [
                'recipe_name' => 'Apple Pie',
                'description' => 'Traditional apple pie with flaky butter crust and cinnamon spice',
                'preparation_time_minutes' => 180,
                'serving_size' => 8,
                'cost_per_unit' => 14.60,
                'active_status' => true,
                'special_instructions' => 'Use mix of tart and sweet apples, vent top crust properly',
                'storage_requirements' => 'Store covered at room temperature for 2 days, then refrigerate',
                'shelf_life_days' => 5,
            ],
            [
                'recipe_name' => 'Seasonal Special - Pumpkin Spice Cake',
                'description' => 'Seasonal autumn cake with pumpkin and warm spices',
                'preparation_time_minutes' => 120,
                'serving_size' => 12,
                'cost_per_unit' => 17.20,
                'active_status' => false,
                'special_instructions' => 'Available September-November only, use fresh pumpkin puree',
                'storage_requirements' => 'Refrigerate after assembly',
                'shelf_life_days' => 3,
            ]
        ];

        foreach ($recipes as $recipeData) {
            Recipe::create($recipeData);
        }

        $this->command->info('Created ' . count($recipes) . ' dessert recipes');
    }
}