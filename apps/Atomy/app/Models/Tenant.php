<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Eloquent Model
 *
 * Implements the TenantInterface from the Nexus\Tenant package.
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $email
 * @property string|null $domain
 * @property string|null $subdomain
 * @property string|null $database_name
 * @property string $status
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon|null $billing_cycle_start_date
 * @property string $timezone
 * @property string $locale
 * @property string $currency
 * @property string $date_format
 * @property string $time_format
 * @property string|null $parent_id
 * @property int|null $storage_quota
 * @property int $storage_used
 * @property int|null $max_users
 * @property int|null $rate_limit
 * @property bool $read_only
 * @property int $onboarding_progress
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tenant extends Model implements TenantInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'email',
        'domain',
        'subdomain',
        'database_name',
        'status',
        'trial_ends_at',
        'billing_cycle_start_date',
        'timezone',
        'locale',
        'currency',
        'date_format',
        'time_format',
        'parent_id',
        'storage_quota',
        'storage_used',
        'max_users',
        'rate_limit',
        'read_only',
        'onboarding_progress',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'billing_cycle_start_date' => 'datetime',
        'storage_quota' => 'integer',
        'storage_used' => 'integer',
        'max_users' => 'integer',
        'rate_limit' => 'integer',
        'read_only' => 'boolean',
        'onboarding_progress' => 'integer',
        'metadata' => 'array',
    ];

    // TenantInterface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    public function getDatabaseName(): ?string
    {
        return $this->database_name;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDateFormat(): string
    {
        return $this->date_format;
    }

    public function getTimeFormat(): string
    {
        return $this->time_format;
    }

    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->getMetadata()[$key] ?? $default;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isArchived(): bool
    {
        return $this->deleted_at !== null;
    }

    public function getTrialEndsAt(): ?\DateTimeInterface
    {
        return $this->trial_ends_at;
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function getStorageQuota(): ?int
    {
        return $this->storage_quota;
    }

    public function getStorageUsed(): int
    {
        return $this->storage_used;
    }

    public function getMaxUsers(): ?int
    {
        return $this->max_users;
    }

    public function getRateLimit(): ?int
    {
        return $this->rate_limit;
    }

    public function isReadOnly(): bool
    {
        return $this->read_only;
    }

    public function getBillingCycleStartDate(): ?\DateTimeInterface
    {
        return $this->billing_cycle_start_date;
    }

    public function getOnboardingProgress(): int
    {
        return $this->onboarding_progress;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    // Eloquent Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Tenant::class, 'parent_id');
    }

    public function impersonations(): HasMany
    {
        return $this->hasMany(TenantImpersonation::class);
    }
}
