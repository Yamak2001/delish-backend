<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecipeIngredient;
use App\Models\Recipe;
use App\Models\InventoryItem;

class RecipeIngredientSeeder extends Seeder
{
    public function run(): void
    {
        // Get recipes and ingredients
        $chocolateCake = Recipe::where('recipe_name', 'Classic Chocolate Cake')->first();
        $vanillaCupcakes = Recipe::where('recipe_name', 'Vanilla Bean Cupcakes')->first();
        $strawberryCheesecake = Recipe::where('recipe_name', 'Strawberry Cheesecake')->first();
        $chocolateChipCookies = Recipe::where('recipe_name', 'Chocolate Chip Cookies')->first();
        
        $flour = InventoryItem::where('item_name', 'All-Purpose Flour')->first();
        $sugar = InventoryItem::where('item_name', 'Granulated Sugar')->first();
        $butter = InventoryItem::where('item_name', 'Unsalted Butter')->first();
        $eggs = InventoryItem::where('item_name', 'Large Eggs')->first();
        $vanilla = InventoryItem::where('item_name', 'Vanilla Extract')->first();
        $chocolate = InventoryItem::where('item_name', 'Semi-Sweet Chocolate Chips')->first();
        $creamCheese = InventoryItem::where('item_name', 'Cream Cheese')->first();
        $strawberries = InventoryItem::where('item_name', 'Fresh Strawberries')->first();
        $bakingPowder = InventoryItem::where('item_name', 'Baking Powder')->first();
        
        $recipeIngredients = [];
        
        // Classic Chocolate Cake ingredients
        if ($chocolateCake && $flour && $sugar && $butter && $eggs && $vanilla && $chocolate) {
            $recipeIngredients = array_merge($recipeIngredients, [
                [
                    'recipe_id' => $chocolateCake->id,
                    'inventory_item_id' => $flour->id,
                    'quantity_required' => 2.5,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Sift before measuring',
                ],
                [
                    'recipe_id' => $chocolateCake->id,
                    'inventory_item_id' => $sugar->id,
                    'quantity_required' => 2.0,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Cream with butter',
                ],
                [
                    'recipe_id' => $chocolateCake->id,
                    'inventory_item_id' => $butter->id,
                    'quantity_required' => 1.0,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Room temperature',
                ],
                [
                    'recipe_id' => $chocolateCake->id,
                    'inventory_item_id' => $eggs->id,
                    'quantity_required' => 4.0,
                    'unit_of_measurement' => 'large',
                    'preparation_notes' => 'Room temperature, add one at a time',
                ],
                [
                    'recipe_id' => $chocolateCake->id,
                    'inventory_item_id' => $vanilla->id,
                    'quantity_required' => 2.0,
                    'unit_of_measurement' => 'teaspoons',
                    'preparation_notes' => 'Pure vanilla extract',
                ],
                [
                    'recipe_id' => $chocolateCake->id,
                    'inventory_item_id' => $chocolate->id,
                    'quantity_required' => 1.5,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Melt for ganache',
                ],
            ]);
        }
        
        // Vanilla Bean Cupcakes ingredients
        if ($vanillaCupcakes && $flour && $sugar && $butter && $eggs && $vanilla && $bakingPowder) {
            $recipeIngredients = array_merge($recipeIngredients, [
                [
                    'recipe_id' => $vanillaCupcakes->id,
                    'inventory_item_id' => $flour->id,
                    'quantity_required' => 2.25,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Sift with baking powder',
                ],
                [
                    'recipe_id' => $vanillaCupcakes->id,
                    'inventory_item_id' => $sugar->id,
                    'quantity_required' => 1.5,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Cream with butter',
                ],
                [
                    'recipe_id' => $vanillaCupcakes->id,
                    'inventory_item_id' => $butter->id,
                    'quantity_required' => 0.75,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Room temperature',
                ],
                [
                    'recipe_id' => $vanillaCupcakes->id,
                    'inventory_item_id' => $eggs->id,
                    'quantity_required' => 3.0,
                    'unit_of_measurement' => 'large',
                    'preparation_notes' => 'Add one at a time',
                ],
                [
                    'recipe_id' => $vanillaCupcakes->id,
                    'inventory_item_id' => $vanilla->id,
                    'quantity_required' => 2.0,
                    'unit_of_measurement' => 'teaspoons',
                    'preparation_notes' => 'Use vanilla bean paste if available',
                ],
                [
                    'recipe_id' => $vanillaCupcakes->id,
                    'inventory_item_id' => $bakingPowder->id,
                    'quantity_required' => 2.5,
                    'unit_of_measurement' => 'teaspoons',
                    'preparation_notes' => 'Fresh baking powder essential',
                ],
            ]);
        }
        
        // Strawberry Cheesecake ingredients
        if ($strawberryCheesecake && $creamCheese && $sugar && $eggs && $strawberries && $vanilla) {
            $recipeIngredients = array_merge($recipeIngredients, [
                [
                    'recipe_id' => $strawberryCheesecake->id,
                    'inventory_item_id' => $creamCheese->id,
                    'quantity_required' => 4.0,
                    'unit_of_measurement' => 'blocks_8oz',
                    'preparation_notes' => 'Room temperature, no lumps',
                ],
                [
                    'recipe_id' => $strawberryCheesecake->id,
                    'inventory_item_id' => $sugar->id,
                    'quantity_required' => 1.0,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Beat until smooth',
                ],
                [
                    'recipe_id' => $strawberryCheesecake->id,
                    'inventory_item_id' => $eggs->id,
                    'quantity_required' => 4.0,
                    'unit_of_measurement' => 'large',
                    'preparation_notes' => 'Add slowly to prevent curdling',
                ],
                [
                    'recipe_id' => $strawberryCheesecake->id,
                    'inventory_item_id' => $strawberries->id,
                    'quantity_required' => 2.0,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Fresh, hulled and sliced',
                ],
                [
                    'recipe_id' => $strawberryCheesecake->id,
                    'inventory_item_id' => $vanilla->id,
                    'quantity_required' => 1.0,
                    'unit_of_measurement' => 'teaspoon',
                    'preparation_notes' => 'Pure vanilla extract',
                ],
            ]);
        }
        
        // Chocolate Chip Cookies ingredients
        if ($chocolateChipCookies && $flour && $butter && $sugar && $eggs && $chocolate) {
            $recipeIngredients = array_merge($recipeIngredients, [
                [
                    'recipe_id' => $chocolateChipCookies->id,
                    'inventory_item_id' => $flour->id,
                    'quantity_required' => 2.25,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Do not over-pack measuring cup',
                ],
                [
                    'recipe_id' => $chocolateChipCookies->id,
                    'inventory_item_id' => $butter->id,
                    'quantity_required' => 1.0,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Room temperature',
                ],
                [
                    'recipe_id' => $chocolateChipCookies->id,
                    'inventory_item_id' => $sugar->id,
                    'quantity_required' => 0.75,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Granulated sugar',
                ],
                [
                    'recipe_id' => $chocolateChipCookies->id,
                    'inventory_item_id' => $eggs->id,
                    'quantity_required' => 2.0,
                    'unit_of_measurement' => 'large',
                    'preparation_notes' => 'Room temperature',
                ],
                [
                    'recipe_id' => $chocolateChipCookies->id,
                    'inventory_item_id' => $chocolate->id,
                    'quantity_required' => 2.0,
                    'unit_of_measurement' => 'cups',
                    'preparation_notes' => 'Semi-sweet chocolate chips',
                ],
            ]);
        }
        
        foreach ($recipeIngredients as $ingredientData) {
            RecipeIngredient::create($ingredientData);
        }
        
        $this->command->info('Created ' . count($recipeIngredients) . ' recipe ingredient relationships');
    }
}