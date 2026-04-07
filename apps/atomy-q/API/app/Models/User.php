<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nexus\Identity\Contracts\UserInterface;

class User extends Model implements UserInterface
{
    use HasUlids;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'email',
        'name',
        'password_hash',
        'role',
        'status',
        'timezone',
        'locale',
        'email_verified_at',
        'last_login_at',
        'failed_login_attempts',
        'lockout_reason',
        'lockout_expires_at',
        'mfa_enabled',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'metadata' => 'array',
        'lockout_expires_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'password_changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getEmail(): string
    {
        return (string) $this->email;
    }

    public function getPasswordHash(): string
    {
        return (string) $this->password_hash;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
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
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        if ($this->status === 'locked') {
            return true;
        }

        if ($this->lockout_expires_at && $this->lockout_expires_at->isFuture()) {
            return true;
        }

        return false;
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
        return $this->password_changed_at ?? null;
    }

    public function hasMfaEnabled(): bool
    {
        return (bool) $this->mfa_enabled;
    }

    public function getMetadata(): ?array
    {
        return is_array($this->metadata) ? $this->metadata : null;
    }
}
