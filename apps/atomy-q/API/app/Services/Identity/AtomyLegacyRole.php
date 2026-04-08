<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\RoleInterface;

/**
 * @internal
 */
final readonly class AtomyLegacyRole implements RoleInterface
{
    private \DateTimeImmutable $createdAt;

    public function __construct(
        private string $name,
        private ?string $tenantId,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        // Stable pseudo-id so callers can include this in role lists without requiring a roles row.
        return 'legacy:' . strtolower(trim($this->name));
    }

    public function getName(): string
    {
        return trim($this->name);
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getTenantId(): ?string
    {
        $tenantId = $this->tenantId;
        if ($tenantId === null) {
            return null;
        }

        $normalized = trim($tenantId);

        return $normalized === '' ? null : $normalized;
    }

    public function isSystemRole(): bool
    {
        return false;
    }

    public function isSuperAdmin(): bool
    {
        $normalized = strtolower(trim($this->name));

        return in_array($normalized, ['super-admin', 'super_admin', 'superadmin'], true);
    }

    public function getParentRoleId(): ?string
    {
        return null;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function requiresMfa(): bool
    {
        return $this->isSuperAdmin();
    }
}
