<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\Enums\StatementType;

/**
 * Statement persistence contract.
 *
 * Handles CRUD operations for generated financial statements.
 */
interface StatementRepositoryInterface
{
    /**
     * Save a financial statement.
     */
    public function save(FinancialStatementInterface $statement): string;

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
     * Find the latest version of a statement.
     */
    public function findLatestVersion(
        string $entityId,
        ReportingPeriod $period,
        StatementType $type
    ): ?FinancialStatementInterface;

    /**
     * Get all versions of a statement.
     *
     * @return FinancialStatementInterface[]
     */
    public function getVersionHistory(
        string $entityId,
        ReportingPeriod $period,
        StatementType $type
    ): array;

    /**
     * Lock a statement (finalize it).
     */
    public function lock(string $id): void;

    /**
     * Delete a statement.
     */
    public function delete(string $id): void;

    /**
     * Check if a statement exists.
     */
    public function exists(string $entityId, ReportingPeriod $period, StatementType $type): bool;
}
