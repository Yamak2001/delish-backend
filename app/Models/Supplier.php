<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'tax_number',
        'supplier_type',
        'status',
        'payment_terms',
        'credit_limit',
        'current_balance',
        'currency',
        'lead_time_days',
        'rating',
        'notes',
        'contact_info',
        'created_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:3',
        'current_balance' => 'decimal:3',
        'rating' => 'decimal:2',
        'contact_info' => 'json',
        'lead_time_days' => 'integer',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function supplierInvoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('supplier_type', $type);
    }

    public function scopeByRating($query, $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    public function hasReachedCreditLimit()
    {
        return $this->current_balance >= $this->credit_limit;
    }

    public function getAvailableCredit()
    {
        return max(0, $this->credit_limit - $this->current_balance);
    }

    public function updateBalance($amount)
    {
        $this->increment('current_balance', $amount);
        return $this;
    }

    public function reduceBalance($amount)
    {
        $this->decrement('current_balance', $amount);
        return $this;
    }

    public function updateRating($newRating)
    {
        $this->update(['rating' => max(1, min(5, $newRating))]);
        return $this;
    }

    public function canCreatePurchaseOrder()
    {
        return $this->isActive() && !$this->hasReachedCreditLimit();
    }

    public function getFormattedAddress()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->postal_code,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }

    public function getPerformanceStats()
    {
        $totalOrders = $this->purchaseOrders()->count();
        $onTimeDeliveries = $this->purchaseOrders()
            ->whereNotNull('actual_delivery_date')
            ->whereRaw('actual_delivery_date <= expected_delivery_date')
            ->count();
        
        return [
            'total_orders' => $totalOrders,
            'on_time_delivery_rate' => $totalOrders > 0 ? ($onTimeDeliveries / $totalOrders) * 100 : 0,
            'current_rating' => $this->rating,
            'outstanding_balance' => $this->current_balance,
            'credit_utilization' => $this->credit_limit > 0 ? ($this->current_balance / $this->credit_limit) * 100 : 0,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->supplier_code)) {
                $model->supplier_code = 'SUP-' . now()->format('Ymd') . '-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
