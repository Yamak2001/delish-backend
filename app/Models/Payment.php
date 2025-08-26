<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'merchant_id',
        'payment_amount',
        'payment_method',
        'payment_date',
        'reference_number',
        'applied_to_invoices',
        'status',
        'recorded_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_amount' => 'decimal:2',
            'payment_date' => 'date',
            'applied_to_invoices' => 'array',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function getAppliedInvoicesCount(): int
    {
        return count($this->applied_to_invoices ?? []);
    }

    public function isFullyApplied(): bool
    {
        $appliedAmount = collect($this->applied_to_invoices ?? [])->sum('amount');
        return $appliedAmount >= $this->payment_amount;
    }
}