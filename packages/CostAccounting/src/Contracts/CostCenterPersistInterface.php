<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Enums\CostCenterStatus;

/**
 * Cost Center Persist Interface
 * 
 * CQRS: Write operations for cost centers.
 * Handles all mutations and persistence operations.
 */
interface CostCenterPersistInterface
{
    /**
     * Save a cost center
     * 
     * @param CostCenter $costCenter Cost center entity
     * @return void
     */
    public function save(CostCenter $costCenter): void;

    /**
     * Delete a cost center
     * 
     * @param string $id Cost center identifier
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Update cost center status
     * 
     * @param string $id Cost center identifier
     * @param CostCenterStatus $status New status
     * @return void
     */
    public function updateStatus(string $id, CostCenterStatus $status): void;

    /**
     * Update parent relationship
     * 
     * @param string $id Cost center identifier
     * @param string|null $parentId Parent cost center identifier
     * @return void
     */
    public function updateParent(string $id, ?string $parentId): void;

    /**
     * Link to budget
     * 
     * @param string $id Cost center identifier
     * @param string $budgetId Budget identifier
     * @return void
     */
    public function linkBudget(string $id, string $budgetId): void;
}
