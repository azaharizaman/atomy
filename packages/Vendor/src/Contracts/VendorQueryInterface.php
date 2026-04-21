<?php

declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

interface VendorQueryInterface
{
    public function findByTenantAndId(string $tenantId, string $vendorId): ?VendorInterface;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, VendorInterface>
     */
    public function search(string $tenantId, array $filters = []): array;
}
