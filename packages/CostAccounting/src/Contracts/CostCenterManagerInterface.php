<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Enums\CostCenterStatus;

/**
 * Cost Center Manager Interface
 * 
 * Provides cost center CRUD operations and management functionality.
 */
interface CostCenterManagerInterface
{
    /**
     * Create a new cost center
     * 
     * @param array<string, mixed> $data Cost center data
     * @return CostCenter
     */
    public function create(array $data): CostCenter;

    /**
     * Update an existing cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @param array<string, mixed> $data Updated cost center data
     * @return CostCenter
     */
    public function update(string $costCenterId, array $data): CostCenter;

    /**
     * Delete a cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @return void
     */
    public function delete(string $costCenterId): void;

    /**
     * Update cost center status
     * 
     * @param string $costCenterId Cost center identifier
     * @param CostCenterStatus $status New status
     * @return void
     */
    public function updateStatus(string $costCenterId, CostCenterStatus $status): void;

    /**
     * Assign parent cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @param string|null $parentCostCenterId Parent cost center identifier (null to remove)
     * @return void
     */
    public function assignParent(string $costCenterId, ?string $parentCostCenterId): void;

    /**
     * Link cost center to budget
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $budgetId Budget identifier
     * @return void
     */
    public function linkBudget(string $costCenterId, string $budgetId): void;
}
