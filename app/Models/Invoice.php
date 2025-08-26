<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'merchant_id',
        'invoice_type',
        'invoice_number',
        'related_order_id',
        'related_job_ticket_id',
        'invoice_amount',
        'issue_date',
        'due_date',
        'payment_status',
        'payment_terms',
        'line_items',
        'tax_amount',
        'total_amount',
        'sent_timestamp',
        'paid_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'invoice_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'line_items' => 'array',
            'sent_timestamp' => 'datetime',
            'paid_timestamp' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    public function relatedJobTicket(): BelongsTo
    {
        return $this->belongsTo(JobTicket::class, 'related_job_ticket_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isOverdue(): bool
    {
        return $this->payment_status !== 'paid' && $this->due_date->isPast();
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }

    public function getRemainingBalance(): float
    {
        $paidAmount = $this->payments()->where('status', 'cleared')->sum('payment_amount');
        return max(0, $this->total_amount - $paidAmount);
    }
}