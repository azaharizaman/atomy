<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Base interface for all tenant coordinators.
 */
interface TenantCoordinatorInterface
{
    /**
     * Get the coordinator name.
     */
    public function getName(): string;

    /**
     * Check if the coordinator has all required data for a tenant.
     */
    public function hasRequiredData(string $tenantId): bool;
}
