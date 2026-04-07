<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Tenant\Contracts\TenantInterface;

final class Tenant extends Model implements TenantInterface
{
    use HasUlids;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'email',
        'status',
        'domain',
        'subdomain',
        'database_name',
        'timezone',
        'locale',
        'currency',
        'date_format',
        'time_format',
        'parent_id',
        'metadata',
        'trial_ends_at',
        'storage_quota',
        'storage_used',
        'max_users',
        'rate_limit',
        'is_readonly',
        'billing_cycle_starts_at',
        'onboarding_progress',
        'retention_hold_until',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_readonly' => 'boolean',
        'storage_quota' => 'integer',
        'storage_used' => 'integer',
        'max_users' => 'integer',
        'rate_limit' => 'integer',
        'onboarding_progress' => 'integer',
        'trial_ends_at' => 'datetime',
        'billing_cycle_starts_at' => 'datetime',
        'retention_hold_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Tenant>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function getId(): string
    {
        return (string) $this->getKey();
    }

    public function getCode(): string
    {
        return $this->nonEmptyString($this->code, '');
    }

    public function getName(): string
    {
        return $this->nonEmptyString($this->name, '');
    }

    public function getEmail(): string
    {
        return $this->nonEmptyString($this->email, '');
    }

    public function getStatus(): string
    {
        return $this->nonEmptyString($this->status, 'pending');
    }

    public function getDomain(): ?string
    {
        return $this->nullableString($this->domain);
    }

    public function getSubdomain(): ?string
    {
        return $this->nullableString($this->subdomain);
    }

    public function getDatabaseName(): ?string
    {
        return $this->nullableString($this->database_name);
    }

    public function getTimezone(): string
    {
        return $this->nonEmptyString($this->timezone, 'UTC');
    }

    public function getLocale(): string
    {
        return $this->nonEmptyString($this->locale, 'en');
    }

    public function getCurrency(): string
    {
        return $this->nonEmptyString($this->currency, 'USD');
    }

    public function getDateFormat(): string
    {
        return $this->nonEmptyString($this->date_format, 'Y-m-d');
    }

    public function getTimeFormat(): string
    {
        return $this->nonEmptyString($this->time_format, 'H:i');
    }

    public function getParentId(): ?string
    {
        return $this->nullableString($this->parent_id);
    }

    public function getMetadata(): array
    {
        return is_array($this->metadata) ? $this->metadata : [];
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        $metadata = $this->getMetadata();

        return array_key_exists($key, $metadata) ? $metadata[$key] : $default;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->getStatus() === 'suspended';
    }

    public function isTrial(): bool
    {
        return $this->getStatus() === 'trial';
    }

    public function isArchived(): bool
    {
        return $this->getStatus() === 'archived';
    }

    public function getTrialEndsAt(): ?\DateTimeInterface
    {
        return $this->trial_ends_at?->toImmutable();
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    public function getStorageQuota(): ?int
    {
        return $this->storage_quota !== null ? (int) $this->storage_quota : null;
    }

    public function getStorageUsed(): int
    {
        return (int) ($this->storage_used ?? 0);
    }

    public function getMaxUsers(): ?int
    {
        return $this->max_users !== null ? (int) $this->max_users : null;
    }

    public function getRateLimit(): ?int
    {
        return $this->rate_limit !== null ? (int) $this->rate_limit : null;
    }

    public function isReadOnly(): bool
    {
        return (bool) $this->is_readonly;
    }

    public function getBillingCycleStartDate(): ?\DateTimeInterface
    {
        return $this->billing_cycle_starts_at?->toImmutable();
    }

    public function getOnboardingProgress(): int
    {
        return (int) ($this->onboarding_progress ?? 0);
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at?->toImmutable();
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at?->toImmutable();
    }

    public function getRetentionHoldUntil(): ?\DateTimeInterface
    {
        return $this->retention_hold_until?->toImmutable();
    }

    public function isQueuedForDeletion(): bool
    {
        return $this->retention_hold_until !== null || $this->trashed();
    }

    private function nonEmptyString(mixed $value, string $default): string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? $default : $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
