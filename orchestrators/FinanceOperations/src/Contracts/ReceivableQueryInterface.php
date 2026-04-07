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
     * @return iterable<int, object> Receipt projections
     */
    public function getExpectedReceipts(string $tenantId, string $periodId): iterable;

    /**
     * Total receivable balance for subledger reconciliation.
     *
     * @return object Balance projection
     */
    public function getTotalBalance(string $tenantId, string $periodId): object;

    /**
     * GL control account code for receivable subledger.
     */
    public function getControlAccountCode(string $tenantId): ?string;

    /**
     * Unposted receivable movements for reconciliation detail.
     *
     * @return iterable<int, object> Movement projections
     */
    public function getUnpostedTransactions(string $tenantId, string $periodId): iterable;
}
