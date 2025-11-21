<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Scopes\TenantScope;

/**
 * Session model
 * 
 * @property string $id
 * @property string $user_id
 * @property string $token
 * @property array $metadata
 * @property string|null $device_fingerprint
 * @property array|null $geographic_location
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Session extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'token',
        'metadata',
        'device_fingerprint',
        'geographic_location',
        'last_activity_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'geographic_location' => 'array',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Apply tenant scope for multi-tenancy isolation
        static::addGlobalScope(new TenantScope());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isRevoked();
    }

    /**
     * Check if session is inactive
     */
    public function isInactive(int $days = 7): bool
    {
        if ($this->last_activity_at === null) {
            // If no activity recorded, use created_at
            return $this->created_at->addDays($days) < now();
        }

        return $this->last_activity_at->addDays($days) < now();
    }

    /**
     * Get relationship to device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(TrustedDevice::class, 'device_fingerprint', 'device_fingerprint');
    }
}
