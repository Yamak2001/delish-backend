<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'workflow_name',
        'description',
        'workflow_steps',
        'estimated_total_duration_minutes',
        'workflow_type',
        'active_status',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'workflow_steps' => 'array',
            'active_status' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_workflow_id');
    }

    public function jobTickets(): HasMany
    {
        return $this->hasMany(JobTicket::class);
    }

    public function getStepByNumber(int $stepNumber): ?array
    {
        $steps = $this->workflow_steps;
        return $steps[$stepNumber - 1] ?? null;
    }

    public function getTotalSteps(): int
    {
        return count($this->workflow_steps);
    }
}