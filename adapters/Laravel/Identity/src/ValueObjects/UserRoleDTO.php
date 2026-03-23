<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\ValueObjects;

use Nexus\Identity\Contracts\RoleInterface;

final class UserRoleDTO implements RoleInterface
{
    public function __construct(
        private readonly string $id,
        private readonly ?string $tenantId = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function isSystemRole(): bool
    {
        return in_array($this->id, ['admin', 'super-admin', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], true);
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->id, ['super-admin', 'ROLE_SUPER_ADMIN'], true);
    }

    public function getParentRoleId(): ?string
    {
        return null;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt ?? new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt ?? new \DateTimeImmutable();
    }

    public function requiresMfa(): bool
    {
        return $this->isSuperAdmin();
    }

    public static function fromDatabaseRow(object $row): self
    {
        return new self(
            id: (string) $row->role_id,
            tenantId: isset($row->tenant_id) ? (string) $row->tenant_id : null,
            createdAt: isset($row->created_at) ? new \DateTimeImmutable($row->created_at) : null,
            updatedAt: isset($row->updated_at) ? new \DateTimeImmutable($row->updated_at) : null,
        );
    }
}
