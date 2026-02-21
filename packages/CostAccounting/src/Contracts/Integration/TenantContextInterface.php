<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Tenant Context Interface
 * 
 * Integration contract for Nexus\Tenant package.
 * Provides multi-entity context information.
 */
interface TenantContextInterface
{
    /**
     * Get current tenant ID
     * 
     * @return string|null
     */
    public function getCurrentTenantId(): ?string;

    /**
     * Get tenant ID from cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @return string|null
     */
    public function getTenantIdForCostCenter(string $costCenterId): ?string;

    /**
     * Validate tenant access
     * 
     * @param string $tenantId Tenant identifier
     * @param string $resourceId Resource identifier
     * @return bool
     */
    public function validateTenantAccess(
        string $tenantId,
        string $resourceId
    ): bool;

    /**
     * Check tenant isolation
     * 
     * @param string $costCenterId1 First cost center
     * @param string $costCenterId2 Second cost center
     * @return bool
     */
    public function areCostCentersInSameTenant(
        string $costCenterId1,
        string $costCenterId2
    ): bool;

    /**
     * Get tenant currency
     * 
     * @param string $tenantId Tenant identifier
     * @return string
     */
    public function getTenantCurrency(string $tenantId): string;
}
