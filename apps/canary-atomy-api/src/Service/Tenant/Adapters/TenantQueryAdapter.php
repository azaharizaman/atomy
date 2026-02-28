<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\TenantRepository;
use Nexus\TenantOperations\Contracts\TenantQueryAdapterInterface;

final readonly class TenantQueryAdapter implements TenantQueryAdapterInterface
{
    public function __construct(
        private TenantRepository $tenantRepository
    ) {}

    public function findById(string $tenantId): ?array
    {
        $tenant = $this->tenantRepository->findById($tenantId);
        if (!$tenant) return null;

        return [
            'id' => $tenant->getId(),
            'code' => $tenant->getCode(),
            'name' => $tenant->getName(),
            'status' => $tenant->getStatus(),
            'plan' => $tenant->getMetadataValue('plan'),
        ];
    }

    public function exists(string $tenantId): bool
    {
        return $this->tenantRepository->findById($tenantId) !== null;
    }
}
