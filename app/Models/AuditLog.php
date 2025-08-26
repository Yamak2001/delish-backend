<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'table_affected',
        'record_id_affected',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'success_status',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'success_status' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getChangedFields(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public static function logActivity(
        string $actionType,
        ?string $tableAffected = null,
        ?string $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        bool $success = true
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'action_type' => $actionType,
            'table_affected' => $tableAffected,
            'record_id_affected' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'success_status' => $success,
        ]);
    }
}