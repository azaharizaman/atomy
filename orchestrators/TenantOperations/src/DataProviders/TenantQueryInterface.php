<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Interface for querying tenant data.
 */
interface TenantQueryInterface
{
    /**
     * @return array{id: string, code: string, name: string, status: string, plan?: string}|null
     */
    public function findById(string $tenantId): ?array;

    public function exists(string $tenantId): bool;
}
