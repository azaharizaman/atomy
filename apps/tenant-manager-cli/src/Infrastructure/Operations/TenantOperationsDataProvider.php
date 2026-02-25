<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\Tenant\Contracts\TenantQueryInterface as DomainTenantQueryInterface;
use Nexus\TenantOperations\Contracts\FeatureQueryAdapterInterface;
use Nexus\TenantOperations\Contracts\SettingsQueryAdapterInterface;
use Nexus\TenantOperations\Contracts\TenantQueryAdapterInterface;

final readonly class TenantOperationsDataProvider implements TenantQueryAdapterInterface, SettingsQueryAdapterInterface, FeatureQueryAdapterInterface
{
    public function __construct(
        private DomainTenantQueryInterface $domainTenantQuery
    ) {}

    public function findById(string $tenantId): ?array
    {
        $tenant = $this->domainTenantQuery->findById($tenantId);
        
        if (!$tenant) {
            return null;
        }

        return [
            'id' => $tenant->getId(),
            'code' => $tenant->getCode(),
            'name' => $tenant->getName(),
            'status' => $tenant->getStatus(),
        ];
    }

    public function exists(string $tenantId): bool
    {
        return $this->domainTenantQuery->findById($tenantId) !== null;
    }

    public function getSettings(string $tenantId): array
    {
        return []; // Simulation
    }

    public function getFeatures(string $tenantId): array
    {
        return []; // Simulation
    }

    public function isEnabled(string $tenantId, string $featureKey): bool
    {
        return false; // Simulation
    }

    public function getAll(string $tenantId): array
    {
        return []; // Simulation
    }
}
