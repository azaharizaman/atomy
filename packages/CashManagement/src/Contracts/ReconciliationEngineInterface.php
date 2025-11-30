<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use Nexus\CashManagement\ValueObjects\ReconciliationTolerance;

/**
 * Reconciliation Engine Interface
 *
 * Core engine for automatic transaction matching.
 */
interface ReconciliationEngineInterface
{
    /**
     * Reconcile a bank statement
     */
    public function reconcileStatement(
        string $statementId,
        ?ReconciliationTolerance $tolerance = null
    ): ReconciliationResultInterface;

    /**
     * Match a single bank transaction
     */
    public function matchTransaction(
        string $bankTransactionId,
        ?ReconciliationTolerance $tolerance = null
    ): ?ReconciliationInterface;

    /**
     * Find potential matches for a bank transaction
     *
     * @return array<ReconciliationInterface>
     */
    public function findPotentialMatches(
        string $bankTransactionId,
        int $limit = 10
    ): array;
}
