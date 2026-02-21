<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostPool;

/**
 * Cost Pool Query Interface
 * 
 * CQRS: Read operations for cost pools.
 * Side-effect-free queries for retrieving cost pool data.
 */
interface CostPoolQueryInterface
{
    /**
     * Find cost pool by ID
     * 
     * @param string $id Cost pool identifier
     * @return CostPool|null
     */
    public function findById(string $id): ?CostPool;

    /**
     * Find cost pool by code
     * 
     * @param string $code Cost pool code
     * @return CostPool|null
     */
    public function findByCode(string $code): ?CostPool;

    /**
     * Find cost pools by cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @return array<CostPool>
     */
    public function findByCostCenter(string $costCenterId): array;

    /**
     * Find cost pools by tenant
     * 
     * @param string $tenantId Tenant identifier
     * @return array<CostPool>
     */
    public function findByTenant(string $tenantId): array;

    /**
     * Find cost pools by period
     * 
     * @param string $periodId Fiscal period identifier
     * @return array<CostPool>
     */
    public function findByPeriod(string $periodId): array;

    /**
     * Find active cost pools
     * 
     * @param string $tenantId Tenant identifier
     * @return array<CostPool>
     */
    public function findActive(string $tenantId): array;
}
