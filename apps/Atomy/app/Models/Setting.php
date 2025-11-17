<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Setting model for managing application, tenant, and user settings.
 *
 * This model stores settings across three scopes:
 * - application: System-wide defaults (read from config)
 * - tenant: Tenant-specific settings
 * - user: User-specific settings
 */
class Setting extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'settings';

    protected $fillable = [
        'scope',
        'scope_id',
        'key',
        'value',
        'type',
        'description',
        'validation_rules',
        'group',
        'is_readonly',
        'is_protected',
        'is_encrypted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'json',
        'validation_rules' => 'array',
        'is_readonly' => 'boolean',
        'is_protected' => 'boolean',
        'is_encrypted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope query to user settings.
     */
    public function scopeUser($query, string $userId)
    {
        return $query->where('scope', 'user')
            ->where('scope_id', $userId);
    }

    /**
     * Scope query to tenant settings.
     */
    public function scopeTenant($query, string $tenantId)
    {
        return $query->where('scope', 'tenant')
            ->where('scope_id', $tenantId);
    }

    /**
     * Scope query to application settings.
     */
    public function scopeApplication($query)
    {
        return $query->where('scope', 'application')
            ->whereNull('scope_id');
    }

    /**
     * Scope query by setting key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope query by key prefix.
     */
    public function scopeByKeyPrefix($query, string $prefix)
    {
        return $query->where('key', 'like', $prefix . '%');
    }

    /**
     * Scope query by group.
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get the setting history.
     */
    public function history()
    {
        return $this->hasMany(SettingHistory::class, 'setting_id');
    }

    /**
     * Get the decrypted value if encrypted.
     */
    public function getDecryptedValue(): mixed
    {
        if ($this->is_encrypted && is_string($this->value)) {
            return decrypt($this->value);
        }

        return $this->value;
    }

    /**
     * Set the value with optional encryption.
     */
    public function setEncryptedValue(mixed $value): void
    {
        if ($this->is_encrypted) {
            $this->value = encrypt($value);
        } else {
            $this->value = $value;
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Track changes in history
        static::created(function (Setting $setting) {
            SettingHistory::create([
                'setting_id' => $setting->id,
                'scope' => $setting->scope,
                'scope_id' => $setting->scope_id,
                'key' => $setting->key,
                'action' => 'created',
                'old_value' => null,
                'new_value' => $setting->value,
                'changed_by' => $setting->created_by,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function (Setting $setting) {
            if ($setting->isDirty('value')) {
                SettingHistory::create([
                    'setting_id' => $setting->id,
                    'scope' => $setting->scope,
                    'scope_id' => $setting->scope_id,
                    'key' => $setting->key,
                    'action' => 'updated',
                    'old_value' => $setting->getOriginal('value'),
                    'new_value' => $setting->value,
                    'changed_by' => $setting->updated_by,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        static::deleted(function (Setting $setting) {
            SettingHistory::create([
                'setting_id' => $setting->id,
                'scope' => $setting->scope,
                'scope_id' => $setting->scope_id,
                'key' => $setting->key,
                'action' => 'deleted',
                'old_value' => $setting->value,
                'new_value' => null,
                'changed_by' => $setting->updated_by,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
