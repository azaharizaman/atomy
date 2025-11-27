<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Flag Override model for user-level feature flag settings.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string $flag_name
 * @property bool $enabled
 * @property array|null $value
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 */
class UserFlagOverride extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_flag_overrides';

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'flag_name',
        'enabled',
        'value',
        'reason',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'value' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => false,
    ];

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the user that owns this override.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the feature flag this override applies to.
     */
    public function featureFlag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class, 'flag_name', 'name')
            ->where('tenant_id', $this->tenant_id);
    }

    // ==========================================
    // Accessor Methods
    // ==========================================

    /**
     * Get the tenant ID.
     */
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): string
    {
        return $this->user_id;
    }

    /**
     * Get the flag name.
     */
    public function getFlagName(): string
    {
        return $this->flag_name;
    }

    /**
     * Check if enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    /**
     * Get the value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the reason for the override.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the expiration date.
     */
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expires_at ? \DateTimeImmutable::createFromMutable($this->expires_at->toDateTime()) : null;
    }

    /**
     * Check if the override has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the override is currently active (not expired).
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    // ==========================================
    // Mutator Methods
    // ==========================================

    /**
     * Set enabled state.
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Set the value.
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set the reason.
     */
    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Set expiration date.
     */
    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expires_at = $expiresAt;
        return $this;
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope query to a specific tenant.
     */
    public function scopeTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to a specific user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to a specific flag.
     */
    public function scopeForFlag($query, string $flagName)
    {
        return $query->where('flag_name', $flagName);
    }

    /**
     * Scope query to active (non-expired) overrides only.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope query to expired overrides only.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    /**
     * Scope query to enabled overrides only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Convert to array for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'flag_name' => $this->flag_name,
            'enabled' => $this->enabled,
            'value' => $this->value,
            'reason' => $this->reason,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }
}
