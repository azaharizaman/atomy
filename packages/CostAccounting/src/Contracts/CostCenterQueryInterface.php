<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\ValueObjects\CostCenterHierarchy;

/**
 * Cost Center Query Interface
 * 
 * CQRS: Read operations for cost centers.
 * Side-effect-free queries for retrieving cost center data.
 */
interface CostCenterQueryInterface
{
    /**
     * Find cost center by ID
     * 
     * @param string $id Cost center identifier
     * @return CostCenter|null
     */
    public function findById(string $id): ?CostCenter;

    /**
     * Find cost center by code
     * 
     * @param string $code Cost center code
     * @return CostCenter|null
     */
    public function findByCode(string $code): ?CostCenter;

    /**
     * Find child cost centers
     * 
     * @param string $parentId Parent cost center identifier
     * @return array<CostCenter>
     */
    public function findChildren(string $parentId): array;

    /**
     * Find cost centers by tenant
     * 
     * @param string $tenantId Tenant identifier
     * @return array<CostCenter>
     */
    public function findByTenant(string $tenantId): array;

    /**
     * Get cost center hierarchy
     * 
     * @param string $rootId Root cost center identifier
     * @return CostCenterHierarchy
     */
    public function getHierarchy(string $rootId): CostCenterHierarchy;

    /**
     * Find all root cost centers (no parent)
     * 
     * @param string $tenantId Tenant identifier
     * @return array<CostCenter>
     */
    public function findRootCostCenters(string $tenantId): array;

    /**
     * Find cost centers by status
     * 
     * @param string $tenantId Tenant identifier
     * @param string $status Cost center status
     * @return array<CostCenter>
     */
    public function findByStatus(string $tenantId, string $status): array;
}
