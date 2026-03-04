<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

/**
 * Tenant context for reporting operations.
 */
interface TenantContextInterface
{
    /**
     * Get the ID of the current tenant.
     * 
     * @return string|null
     */
    public function getTenantId(): ?string;
}
