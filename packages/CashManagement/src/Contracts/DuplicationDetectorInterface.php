<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Duplication Detector Interface
 *
 * Two-phase strategy for detecting duplicate bank statements.
 */
interface DuplicationDetectorInterface
{
    /**
     * Check if statement is duplicate using fast hash
     */
    public function isDuplicate(
        string $bankAccountId,
        string $startDate,
        string $endDate,
        string $totalDebit,
        string $totalCredit
    ): bool;

    /**
     * Get existing statement ID if duplicate found
     */
    public function getExistingStatementId(string $statementHash): ?string;

    /**
     * Check for partial overlap with existing statements
     *
     * @return array<string> Array of overlapping statement IDs
     */
    public function checkOverlap(
        string $bankAccountId,
        string $startDate,
        string $endDate
    ): array;

    /**
     * Perform line-by-line comparison for merge detection
     */
    public function compareLines(
        string $statementId,
        array $newTransactionLines
    ): array;
}
