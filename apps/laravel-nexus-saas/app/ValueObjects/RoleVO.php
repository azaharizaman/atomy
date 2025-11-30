<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Nexus\Domain\Identity\Contracts\RoleInterface;

/**
 * Role Value Object implementing Nexus RoleInterface
 *
 * Maps Laravel role model/data to domain contract
 */
final readonly class RoleVO implements RoleInterface
{
    public function __construct(
        private string $id,
        private string $name,
        private ?string $description,
        private ?string $tenantId,
        private bool $isSystemRole,
        private bool $isSuperAdmin,
        private ?string $parentRoleId,
        private \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private bool $requiresMfa = false,
    ) {}

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
        return $this->tenantId;
    }

    public function isSystemRole(): bool
    {
        return $this->isSystemRole;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }

    public function getParentRoleId(): ?string
    {
        return $this->parentRoleId;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function requiresMfa(): bool
    {
        return $this->requiresMfa;
    }

    /**
     * Factory method to create RoleVO from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            tenantId: $data['tenant_id'] ?? null,
            isSystemRole: $data['is_system_role'] ?? false,
            isSuperAdmin: strtolower($data['name']) === 'superadmin',
            parentRoleId: $data['parent_role_id'] ?? null,
            createdAt: $data['created_at'] instanceof \DateTimeInterface 
                ? $data['created_at'] 
                : new \DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: $data['updated_at'] instanceof \DateTimeInterface 
                ? $data['updated_at'] 
                : new \DateTimeImmutable($data['updated_at'] ?? 'now'),
            requiresMfa: $data['requires_mfa'] ?? false,
        );
    }
}
