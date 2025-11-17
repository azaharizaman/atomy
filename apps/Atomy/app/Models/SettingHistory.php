<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setting history model for tracking setting changes.
 */
class SettingHistory extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'setting_history';

    public $timestamps = false;

    protected $fillable = [
        'setting_id',
        'scope',
        'scope_id',
        'key',
        'action',
        'old_value',
        'new_value',
        'changed_by',
        'ip_address',
        'user_agent',
        'changed_at',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the setting this history entry belongs to.
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'setting_id');
    }

    /**
     * Scope query by setting ID.
     */
    public function scopeBySetting($query, string $settingId)
    {
        return $query->where('setting_id', $settingId);
    }

    /**
     * Scope query by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope query by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope query by changed by (user).
     */
    public function scopeByChangedBy($query, string $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Scope query by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }
}
