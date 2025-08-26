<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteManagement extends Model
{
    protected $fillable = [
        'merchant_id',
        'scheduled_collection_date',
        'assigned_driver_id',
        'collection_status',
        'actual_collection_date',
        'waste_items_collected',
        'photos',
        'total_waste_value',
        'credited_to_merchant',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_collection_date' => 'date',
            'actual_collection_date' => 'datetime',
            'waste_items_collected' => 'array',
            'photos' => 'array',
            'total_waste_value' => 'decimal:2',
            'credited_to_merchant' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function isOverdue(): bool
    {
        return $this->collection_status === 'scheduled' && 
               $this->scheduled_collection_date->isPast();
    }

    public function getItemsCount(): int
    {
        return count($this->waste_items_collected ?? []);
    }
}