<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nexus\Identity\Contracts\RoleInterface;

class Role extends Model implements RoleInterface
{
    use HasUlids;

    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'parent_role_id',
        'name',
        'description',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function getId(): string
    {
        return (string) $this->getKey();
    }

    public function getTenantId(): ?string
    {
        $tenantId = trim((string) ($this->tenant_id ?? ''));

        return $tenantId === '' ? null : $tenantId;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function getDescription(): ?string
    {
        $description = trim((string) ($this->description ?? ''));

        return $description === '' ? null : $description;
    }

    public function getParentRoleId(): ?string
    {
        $parentRoleId = trim((string) ($this->parent_role_id ?? ''));

        return $parentRoleId === '' ? null : $parentRoleId;
    }

    public function isSystemRole(): bool
    {
        return $this->getTenantId() === null;
    }

    public function isSuperAdmin(): bool
    {
        $normalized = strtolower(trim((string) $this->name));

        return in_array($normalized, ['super-admin', 'super_admin', 'superadmin'], true);
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function requiresMfa(): bool
    {
        return $this->isSuperAdmin();
    }
}
