<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\ConsolidatedStatement;
use Nexus\Accounting\Domain\Exceptions\ConsolidationException;

/**
 * Persistence interface for consolidated statements.
 *
 * Follows CQRS pattern - write operations only.
 */
interface ConsolidationPersistInterface
{
    /**
     * Save a consolidated statement.
     *
     * @param ConsolidatedStatement $statement The statement to save
     * @return ConsolidatedStatement The saved statement
     * @throws ConsolidationException If save operation fails
     */
    public function save(ConsolidatedStatement $statement): ConsolidatedStatement;

    /**
     * Delete a consolidated statement.
     *
     * @param string $id Statement ID to delete
     * @throws ConsolidationException If delete operation fails
     */
    public function delete(string $id): void;

    /**
     * Delete all consolidated statements for a parent tenant and period.
     *
     * @param string $parentTenantId Parent tenant identifier
     * @param string $periodId Period identifier
     * @throws ConsolidationException If delete operation fails
     */
    public function deleteByParentAndPeriod(string $parentTenantId, string $periodId): void;
}
