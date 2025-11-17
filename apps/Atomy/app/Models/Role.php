<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Identity\Contracts\RoleInterface;
use App\Scopes\TenantScope;

/**
 * Role model
 * 
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string|null $tenant_id
 * @property bool $is_system_role
 * @property bool $is_super_admin
 * @property string|null $parent_role_id
 * @property bool $requires_mfa
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Role extends Model implements RoleInterface
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'tenant_id',
        'is_system_role',
        'is_super_admin',
        'parent_role_id',
        'requires_mfa',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'is_super_admin' => 'boolean',
        'requires_mfa' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    // Relationships

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    public function childRoles()
    {
        return $this->hasMany(Role::class, 'parent_role_id');
    }

    // RoleInterface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }

    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    public function getParentRoleId(): ?string
    {
        return $this->parent_role_id;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function requiresMfa(): bool
    {
        return $this->requires_mfa;
    }
}
