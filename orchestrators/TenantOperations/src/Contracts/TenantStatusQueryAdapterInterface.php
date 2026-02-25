<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for querying tenant status.
 */
interface TenantStatusQueryAdapterInterface
{
    public function isActive(string $tenantId): bool;
    public function getStatus(string $tenantId): ?string;
}
