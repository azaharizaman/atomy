<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use Nexus\CashManagement\ValueObjects\ReconciliationTolerance;

/**
 * Reconciliation Result Interface
 *
 * Result of a reconciliation operation.
 */
interface ReconciliationResultInterface
{
    /**
     * Get count of matched transactions
     */
    public function getMatchedCount(): int;

    /**
     * Get count of unmatched transactions
     */
    public function getUnmatchedCount(): int;

    /**
     * Get count of high-confidence matches
     */
    public function getHighConfidenceCount(): int;

    /**
     * Get IDs of matched transactions
     *
     * @return array<string>
     */
    public function getMatchedTransactionIds(): array;

    /**
     * Get IDs of unmatched transactions
     *
     * @return array<string>
     */
    public function getUnmatchedTransactionIds(): array;

    /**
     * Get total amount variance
     */
    public function getTotalVariance(): string;

    /**
     * Get tolerance used for matching
     */
    public function getTolerance(): ReconciliationTolerance;

    /**
     * Check if reconciliation was successful
     */
    public function isSuccessful(): bool;

    /**
     * Get any error messages
     *
     * @return array<string>
     */
    public function getErrors(): array;
}
