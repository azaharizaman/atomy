<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\ValueObjects\UserStatus;
use App\Scopes\TenantScope;

/**
 * User model
 * 
 * @property string $id
 * @property string $email
 * @property string $password_hash
 * @property string $status
 * @property string|null $name
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $password_changed_at
 * @property int $failed_login_attempts
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property bool $mfa_enabled
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class User extends Model implements UserInterface
{
    use HasUlids;

    protected $fillable = [
        'email',
        'password_hash',
        'status',
        'name',
        'tenant_id',
        'email_verified_at',
        'password_changed_at',
        'mfa_enabled',
        'metadata',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'failed_login_attempts' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    // Relationships

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps();
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function mfaEnrollments(): HasMany
    {
        return $this->hasMany(MfaEnrollment::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    // UserInterface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->password_hash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->email_verified_at;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE->value;
    }

    public function isLocked(): bool
    {
        return $this->status === UserStatus::LOCKED->value;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }

    public function getPasswordChangedAt(): ?\DateTimeInterface
    {
        return $this->password_changed_at;
    }

    public function hasMfaEnabled(): bool
    {
        return $this->mfa_enabled;
    }
}
