<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $fillable = [
        'business_name',
        'location_address',
        'contact_person_name',
        'contact_phone',
        'contact_email',
        'payment_terms',
        'credit_limit',
        'account_status',
        'whatsapp_business_phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(MerchantPricing::class);
    }

    public function productTracking(): HasMany
    {
        return $this->hasMany(MerchantProductTracking::class);
    }

    public function wasteManagement(): HasMany
    {
        return $this->hasMany(WasteManagement::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
