<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Tenant as TenantResource;
use Nexus\Tenant\Contracts\TenantQueryInterface;

/**
 * Collection provider for Tenant resource.
 *
 * Fetches all tenants using the Tenant package.
 */
final class TenantCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly TenantQueryInterface $tenantQuery
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<TenantResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $result = $this->tenantQuery->all();
        $tenants = is_array($result) && isset($result['data']) ? $result['data'] : $result;

        foreach ($tenants as $tenant) {
            $resource = new TenantResource();
            $resource->id = $tenant->getId();
            $resource->name = $tenant->getName();
            $resource->code = $tenant->getCode();
            $resource->domain = $tenant->getDomain();
            $resource->status = $tenant->getStatus();
            $resource->createdAt = $tenant->getCreatedAt()?->format('Y-m-d H:i:s');

            yield $resource;
        }
    }
}
