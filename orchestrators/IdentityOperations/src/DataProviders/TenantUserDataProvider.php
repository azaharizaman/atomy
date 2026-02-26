<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

use Nexus\IdentityOperations\Contracts\TenantUserProviderInterface;

/**
 * Data provider for tenant user data.
 */
final readonly class TenantUserDataProvider implements TenantUserProviderInterface
{
    public function __construct(
        private TenantUserQueryInterface $tenantUserQuery,
    ) {}

    /**
     * @return array<int, array{id: string, email: string, name: string, status: string}>
     */
    public function getTenantUsers(string $tenantId): array
    {
        return $this->tenantUserQuery->findByTenantId($tenantId);
    }

    public function userBelongsToTenant(string $userId, string $tenantId): bool
    {
        return $this->tenantUserQuery->userBelongsToTenant($userId, $tenantId);
    }

    /**
     * @return array<int, string>
     */
    public function getUserTenantRoles(string $userId, string $tenantId): array
    {
        return $this->tenantUserQuery->getUserRoles($userId, $tenantId);
    }

    public function isTenantActive(string $tenantId): bool
    {
        return $this->tenantUserQuery->isTenantActive($tenantId);
    }
}
