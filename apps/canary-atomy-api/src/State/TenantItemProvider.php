<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Tenant as TenantResource;
use Nexus\Tenant\Contracts\TenantQueryInterface;

/**
 * Item provider for Tenant resource.
 *
 * Fetches a single tenant using the Tenant package.
 */
final class TenantItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly TenantQueryInterface $tenantQuery
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return TenantResource|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?TenantResource
    {
        $id = $uriVariables['id'] ?? null;
        if (!$id) {
            return null;
        }

        $tenant = $this->tenantQuery->findById($id);
        if (!$tenant) {
            return null;
        }

        $resource = new TenantResource();
        $resource->id = $tenant->getId();
        $resource->name = $tenant->getName();
        $resource->code = $tenant->getCode();
        $resource->domain = $tenant->getDomain();
        $resource->status = $tenant->getStatus();
        $resource->createdAt = $tenant->getCreatedAt()?->format('Y-m-d H:i:s');

        return $resource;
    }
}
