<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantContext;

/**
 * Interface for providing tenant context data.
 */
interface TenantContextProviderInterface
{
    /**
     * Get complete tenant context.
     */
    public function getContext(string $tenantId): TenantContext;

    /**
     * Check if tenant exists.
     */
    public function tenantExists(string $tenantId): bool;
}
