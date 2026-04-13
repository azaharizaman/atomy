<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract for receivable projections used by finance orchestrators.
 */
interface ReceivableQueryInterface
{
    /**
     * Expected incoming receipts for forecast.
     *
     * @return iterable<int, ReceiptProjection> Receipt projections
     */
    public function getExpectedReceipts(string $tenantId, string $periodId): iterable;

    /**
     * Total receivable balance for subledger reconciliation.
     *
     * @return BalanceProjection Balance projection
     */
    public function getTotalBalance(string $tenantId, string $periodId): BalanceProjection;

    /**
     * GL control account code for receivable subledger.
     */
    public function getControlAccountCode(string $tenantId): ?string;

    /**
     * Unposted receivable movements for reconciliation detail.
     *
     * @return iterable<int, MovementProjection> Movement projections
     */
    public function getUnpostedTransactions(string $tenantId, string $periodId): iterable;
}
