<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'movement_type',
        'quantity_change',
        'unit_cost',
        'reference_id',
        'performed_by_user_id',
        'notes',
        'batch_number',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function getTotalValue(): float
    {
        return abs($this->quantity_change) * $this->unit_cost;
    }
}