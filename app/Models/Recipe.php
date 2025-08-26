<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    protected $fillable = [
        'recipe_name',
        'description',
        'preparation_time_minutes',
        'serving_size',
        'cost_per_unit',
        'active_status',
        'special_instructions',
        'storage_requirements',
        'shelf_life_days',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_unit' => 'decimal:2',
            'active_status' => 'boolean',
        ];
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'recipe_ingredients')
                    ->withPivot('quantity_required', 'unit_of_measurement', 'preparation_notes')
                    ->withTimestamps();
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function merchantPricing(): HasMany
    {
        return $this->hasMany(MerchantPricing::class);
    }

    public function merchantProductTracking(): HasMany
    {
        return $this->hasMany(MerchantProductTracking::class);
    }
}