<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Exceptions\StatementNotFoundException;
use Nexus\Accounting\Domain\Exceptions\StatementPersistenceException;

/**
 * Persistence interface for financial statements.
 *
 * Follows CQRS pattern - write operations only.
 */
interface FinancialStatementPersistInterface
{
    /**
     * Save a financial statement (create or update).
     *
     * @param FinancialStatementInterface $statement The statement to save
     * @return FinancialStatementInterface The saved statement
     * @throws StatementPersistenceException If save operation fails
     */
    public function save(FinancialStatementInterface $statement): FinancialStatementInterface;

    /**
     * Delete a financial statement.
     *
     * @param string $id Statement ID to delete
     * @throws StatementNotFoundException If statement not found
     * @throws StatementPersistenceException If delete operation fails
     */
    public function delete(string $id): void;

    /**
     * Delete all statements for a tenant and period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @throws StatementPersistenceException If delete operation fails
     */
    public function deleteByTenantAndPeriod(string $tenantId, string $periodId): void;
}
