<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'merchant_id',
        'whatsapp_order_id',
        'order_items',
        'total_amount',
        'order_date',
        'requested_delivery_date',
        'order_status',
        'special_notes',
        'delivery_address',
        'assigned_workflow_id',
        'payment_terms_override',
    ];

    protected function casts(): array
    {
        return [
            'order_items' => 'array',
            'total_amount' => 'decimal:2',
            'order_date' => 'datetime',
            'requested_delivery_date' => 'date',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'assigned_workflow_id');
    }

    public function jobTicket(): HasOne
    {
        return $this->hasOne(JobTicket::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'related_order_id');
    }
}