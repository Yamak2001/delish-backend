<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'updated_by_user_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function getTypedValue()
    {
        return match($this->setting_type) {
            'number' => (float) $this->setting_value,
            'boolean' => (bool) $this->setting_value,
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->getTypedValue() : $default;
    }

    public static function setValue(string $key, $value, string $type = 'string', string $description = ''): self
    {
        $settingValue = match($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $settingValue,
                'setting_type' => $type,
                'description' => $description,
                'updated_by_user_id' => auth()->id(),
            ]
        );
    }
}