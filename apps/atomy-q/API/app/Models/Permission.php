<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Identity\Contracts\PermissionInterface;

class Permission extends Model implements PermissionInterface
{
    use HasUlids;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function getResource(): string
    {
        $name = trim((string) $this->name);
        $parts = explode('.', $name, 2);

        return $parts[0] !== '' ? $parts[0] : $name;
    }

    public function getAction(): string
    {
        $name = trim((string) $this->name);
        $parts = explode('.', $name, 2);

        return $parts[1] ?? '*';
    }

    public function getDescription(): ?string
    {
        return $this->description !== null ? (string) $this->description : null;
    }

    public function isWildcard(): bool
    {
        return str_ends_with(trim((string) $this->name), '.*') || $this->getAction() === '*';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function matches(string $permissionName): bool
    {
        $candidate = strtolower(trim($permissionName));
        $granted = strtolower(trim((string) $this->name));

        if ($granted === '' || $candidate === '') {
            return false;
        }

        if ($granted === '*' || $candidate === '*') {
            return true;
        }

        if ($granted === $candidate) {
            return true;
        }

        if (str_ends_with($granted, '.*')) {
            $prefix = substr($granted, 0, -2);

            return $prefix !== '' && ($candidate === $prefix || str_starts_with($candidate, $prefix . '.'));
        }

        if (str_ends_with($candidate, '.*')) {
            $prefix = substr($candidate, 0, -2);

            return $prefix !== '' && ($granted === $prefix || str_starts_with($granted, $prefix . '.'));
        }

        return false;
    }
}
