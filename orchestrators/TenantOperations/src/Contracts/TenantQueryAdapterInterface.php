<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for querying tenant data.
 */
interface TenantQueryAdapterInterface
{
    /**
     * @return array{id: string, code: string, name: string, status: string, plan?: string}|null
     */
    public function findById(string $tenantId): ?array;

    public function exists(string $tenantId): bool;
}
