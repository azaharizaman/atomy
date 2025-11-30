<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\Enums\StatementType;

/**
 * Statement Repository interface.
 *
 * Persistence interface for financial statements.
 */
interface StatementRepositoryInterface
{
    /**
     * Find a statement by ID.
     */
    public function findById(string $id): ?FinancialStatementInterface;

    /**
     * Find statements by entity and period.
     *
     * @return FinancialStatementInterface[]
     */
    public function findByEntityAndPeriod(
        string $entityId,
        ReportingPeriod $period,
        ?StatementType $type = null
    ): array;

    /**
     * Save a statement.
     */
    public function save(FinancialStatementInterface $statement): string;

    /**
     * Delete a statement.
     */
    public function delete(string $id): void;

    /**
     * Lock a statement (make it immutable).
     */
    public function lock(string $id): void;

    /**
     * Get the latest statement of a type for an entity.
     */
    public function getLatest(string $entityId, StatementType $type): ?FinancialStatementInterface;
}
