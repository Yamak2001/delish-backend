<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantProductTracking extends Model
{
    protected $table = 'merchant_product_tracking';
    
    protected $fillable = [
        'merchant_id',
        'recipe_id',
        'quantity_delivered',
        'delivery_date',
        'expiration_date',
        'current_estimated_quantity',
        'status',
        'job_ticket_id',
        'driver_notes',
        'collection_required',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delivered' => 'decimal:3',
            'current_estimated_quantity' => 'decimal:3',
            'delivery_date' => 'datetime',
            'expiration_date' => 'date',
            'collection_required' => 'boolean',
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

    public function jobTicket(): BelongsTo
    {
        return $this->belongsTo(JobTicket::class);
    }

    public function getDaysUntilExpiration(): int
    {
        return now()->diffInDays($this->expiration_date, false);
    }

    public function isExpired(): bool
    {
        return $this->expiration_date->isPast();
    }

    public function isNearExpiration(): bool
    {
        return $this->getDaysUntilExpiration() <= 1;
    }
}