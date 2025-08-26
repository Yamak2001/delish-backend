<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'phone_number',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdWorkflows()
    {
        return $this->hasMany(Workflow::class, 'created_by_user_id');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'performed_by_user_id');
    }

    public function assignedJobTicketSteps()
    {
        return $this->hasMany(JobTicketStep::class, 'assigned_user_id');
    }

    public function completedJobTicketSteps()
    {
        return $this->hasMany(JobTicketStep::class, 'completed_by_user_id');
    }

    public function createdPricing()
    {
        return $this->hasMany(MerchantPricing::class, 'created_by_user_id');
    }

    public function assignedWasteCollections()
    {
        return $this->hasMany(WasteManagement::class, 'assigned_driver_id');
    }

    public function recordedPayments()
    {
        return $this->hasMany(Payment::class, 'recorded_by_user_id');
    }

    public function updatedSystemSettings()
    {
        return $this->hasMany(SystemSetting::class, 'updated_by_user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}
