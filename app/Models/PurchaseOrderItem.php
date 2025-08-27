<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'item_description',
        'quantity_ordered',
        'quantity_received',
        'quantity_outstanding',
        'unit_of_measure',
        'unit_price',
        'line_total',
        'discount_percentage',
        'discount_amount',
        'expected_date',
        'item_notes',
        'status',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'quantity_outstanding' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'line_total' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:3',
        'expected_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->quantity_outstanding = $model->quantity_ordered - $model->quantity_received;
        });
    }
}
