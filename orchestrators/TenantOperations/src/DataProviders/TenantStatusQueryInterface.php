<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Interface for querying tenant status.
 */
interface TenantStatusQueryInterface
{
    public function isActive(string $tenantId): bool;
    public function getStatus(string $tenantId): ?string;
}
