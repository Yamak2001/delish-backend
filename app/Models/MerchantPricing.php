<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantPricing extends Model
{
    protected $fillable = [
        'merchant_id',
        'recipe_id',
        'base_cost',
        'merchant_price',
        'markup_percentage',
        'effective_date',
        'expiration_date',
        'price_tier',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'merchant_price' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'effective_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->effective_date <= today() && 
               ($this->expiration_date === null || $this->expiration_date >= today());
    }
}