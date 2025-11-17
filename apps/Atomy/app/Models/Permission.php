<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Identity\Contracts\PermissionInterface;

/**
 * Permission model
 * 
 * @property string $id
 * @property string $name
 * @property string $resource
 * @property string $action
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Permission extends Model implements PermissionInterface
{
    use HasUlids;

    protected $fillable = [
        'name',
        'resource',
        'action',
        'description',
    ];

    // Relationships

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withTimestamps();
    }

    // PermissionInterface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isWildcard(): bool
    {
        return $this->action === '*';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function matches(string $permissionName): bool
    {
        if ($this->name === $permissionName) {
            return true;
        }

        if ($this->isWildcard()) {
            $parts = explode('.', $permissionName, 2);
            return count($parts) === 2 && $parts[0] === $this->resource;
        }

        return false;
    }
}
