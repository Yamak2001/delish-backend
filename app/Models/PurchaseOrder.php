<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'po_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'subtotal_amount',
        'tax_rate',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'received_amount',
        'currency',
        'payment_terms',
        'delivery_address',
        'notes',
        'terms_conditions',
        'priority',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal_amount' => 'decimal:3',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:3',
        'shipping_cost' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'received_amount' => 'decimal:3',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopePartiallyReceived($query)
    {
        return $query->where('status', 'partially_received');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['sent', 'confirmed', 'partially_received'])
                    ->where('expected_delivery_date', '<', now()->toDateString());
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent']);
    }

    // Helper methods
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isReceived()
    {
        return $this->status === 'received';
    }

    public function isPartiallyReceived()
    {
        return $this->status === 'partially_received';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isOverdue()
    {
        return in_array($this->status, ['sent', 'confirmed', 'partially_received']) &&
               $this->expected_delivery_date < now()->toDateString();
    }

    public function isApproved()
    {
        return !is_null($this->approved_by);
    }

    public function canBeSent()
    {
        return $this->status === 'draft' && $this->isApproved();
    }

    public function canBeReceived()
    {
        return in_array($this->status, ['sent', 'confirmed', 'partially_received']);
    }

    public function calculateTaxAmount()
    {
        $taxableAmount = $this->subtotal_amount - $this->discount_amount;
        return ($taxableAmount * $this->tax_rate);
    }

    public function calculateDiscountAmount()
    {
        return ($this->subtotal_amount * $this->discount_percentage) / 100;
    }

    public function calculateTotalAmount()
    {
        $subtotal = $this->subtotal_amount;
        $discount = $this->calculateDiscountAmount();
        $tax = $this->calculateTaxAmount();
        $shipping = $this->shipping_cost;
        
        return $subtotal - $discount + $tax + $shipping;
    }

    public function getOutstandingAmount()
    {
        return max(0, $this->total_amount - $this->received_amount);
    }

    public function getReceiptPercentage()
    {
        return $this->total_amount > 0 ? ($this->received_amount / $this->total_amount) * 100 : 0;
    }

    public function approve($userId = null)
    {
        $this->update([
            'approved_by' => $userId ?: auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }

    public function sendToSupplier()
    {
        if (!$this->canBeSent()) {
            throw new \Exception('Purchase order must be approved before sending');
        }

        $this->update(['status' => 'sent']);
        return $this;
    }

    public function confirm()
    {
        if (!$this->isSent()) {
            throw new \Exception('Purchase order must be sent before confirming');
        }

        $this->update(['status' => 'confirmed']);
        return $this;
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ? $this->notes . "\n\nCancellation reason: " . $reason : $this->notes,
        ]);

        return $this;
    }

    public function recalculateAmounts()
    {
        $itemsTotal = $this->items()->sum('line_total');
        
        $this->update([
            'subtotal_amount' => $itemsTotal,
            'discount_amount' => $this->calculateDiscountAmount(),
            'tax_amount' => $this->calculateTaxAmount(),
            'total_amount' => $this->calculateTotalAmount(),
        ]);

        return $this;
    }

    public function updateReceiptStatus()
    {
        $totalOrdered = $this->items()->sum('quantity_ordered');
        $totalReceived = $this->items()->sum('quantity_received');

        if ($totalReceived == 0) {
            $status = in_array($this->status, ['sent', 'confirmed']) ? $this->status : 'confirmed';
        } elseif ($totalReceived >= $totalOrdered) {
            $status = 'received';
        } else {
            $status = 'partially_received';
        }

        $this->update(['status' => $status]);
        return $this;
    }

    public function addItem($inventoryItemId, $quantity, $unitPrice, $description = null)
    {
        $lineTotal = $quantity * $unitPrice;

        $item = $this->items()->create([
            'inventory_item_id' => $inventoryItemId,
            'item_description' => $description,
            'quantity_ordered' => $quantity,
            'quantity_outstanding' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ]);

        $this->recalculateAmounts();
        return $item;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->po_number)) {
                $model->po_number = 'PO-' . now()->format('Ymd') . '-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function ($model) {
            $model->discount_amount = $model->calculateDiscountAmount();
            $model->tax_amount = $model->calculateTaxAmount();
            $model->total_amount = $model->calculateTotalAmount();
        });
    }
}
