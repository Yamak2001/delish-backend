<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTicketStep extends Model
{
    protected $fillable = [
        'job_ticket_id',
        'step_number',
        'step_name',
        'assigned_role',
        'assigned_user_id',
        'step_type',
        'status',
        'start_timestamp',
        'completion_timestamp',
        'completed_by_user_id',
        'notes',
        'time_spent_minutes',
        'quality_check_passed',
        'next_step_override',
    ];

    protected function casts(): array
    {
        return [
            'start_timestamp' => 'datetime',
            'completion_timestamp' => 'datetime',
            'quality_check_passed' => 'boolean',
        ];
    }

    public function jobTicket(): BelongsTo
    {
        return $this->belongsTo(JobTicket::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function markAsCompleted(User $user, ?string $notes = null, ?bool $qualityPassed = null): void
    {
        $this->update([
            'status' => 'completed',
            'completion_timestamp' => now(),
            'completed_by_user_id' => $user->id,
            'notes' => $notes,
            'quality_check_passed' => $qualityPassed,
            'time_spent_minutes' => $this->start_timestamp ? 
                now()->diffInMinutes($this->start_timestamp) : null,
        ]);
    }

    public function startStep(User $user): void
    {
        $this->update([
            'status' => 'active',
            'start_timestamp' => now(),
            'assigned_user_id' => $user->id,
        ]);
    }
}