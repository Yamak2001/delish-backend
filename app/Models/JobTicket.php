<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTicket extends Model
{
    protected $fillable = [
        'order_id',
        'workflow_id',
        'job_ticket_number',
        'priority_level',
        'current_status',
        'current_step_number',
        'assigned_users',
        'start_timestamp',
        'estimated_completion_timestamp',
        'actual_completion_timestamp',
        'total_production_cost',
        'quality_notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_users' => 'array',
            'start_timestamp' => 'datetime',
            'estimated_completion_timestamp' => 'datetime',
            'actual_completion_timestamp' => 'datetime',
            'total_production_cost' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(JobTicketStep::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'related_job_ticket_id');
    }

    public function merchantProductTracking(): HasMany
    {
        return $this->hasMany(MerchantProductTracking::class);
    }
}