<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'item_name',
        'qr_code',
        'current_quantity',
        'unit_of_measurement',
        'cost_per_unit',
        'supplier_name',
        'supplier_contact',
        'minimum_stock_level',
        'maximum_stock_level',
        'shelf_life_days',
        'storage_requirements',
        'category',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:3',
            'cost_per_unit' => 'decimal:2',
            'minimum_stock_level' => 'decimal:3',
            'maximum_stock_level' => 'decimal:3',
        ];
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
                    ->withPivot('quantity_required', 'unit_of_measurement', 'preparation_notes')
                    ->withTimestamps();
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_quantity <= $this->minimum_stock_level;
    }

    public function getCostContribution(float $quantity): float
    {
        return $this->cost_per_unit * $quantity;
    }
}