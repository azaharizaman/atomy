<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Identity\Contracts\TrustedDeviceInterface;
use App\Scopes\TenantScope;

/**
 * Trusted Device model
 * 
 * @property string $id
 * @property string $user_id
 * @property string|null $tenant_id
 * @property string $device_fingerprint
 * @property string|null $device_name
 * @property bool $is_trusted
 * @property array|null $geographic_location
 * @property array $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $trusted_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TrustedDevice extends Model implements TrustedDeviceInterface
{
    use HasUlids;

    protected $table = 'trusted_devices';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'device_fingerprint',
        'device_name',
        'is_trusted',
        'geographic_location',
        'metadata',
        'ip_address',
        'user_agent',
        'trusted_at',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'geographic_location' => 'array',
        'metadata' => 'array',
        'trusted_at' => 'datetime',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
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
     * Scope a query to only include valid devices
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('revoked_at')
                ->orWhere('revoked_at', '>', now());
        })->where('expires_at', '>', now());
    }

    // ============================================
    // TrustedDeviceInterface Implementation
    // ============================================

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getFingerprint(): string
    {
        return $this->device_fingerprint;
    }

    public function getDeviceName(): ?string
    {
        return $this->device_name;
    }

    public function isTrusted(): bool
    {
        return $this->is_trusted ?? false;
    }

    public function getTrustedAt(): ?\DateTimeInterface
    {
        return $this->trusted_at;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->last_used_at;
    }

    public function getGeographicLocation(): ?array
    {
        return $this->geographic_location;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }
}
