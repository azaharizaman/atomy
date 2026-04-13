<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract for payable projections used by finance orchestrators.
 */
interface PayableQueryInterface
{
    /**
     * Expected outgoing payments for forecast.
     *
     * @return iterable<int, object> Payment projections
     */
    public function getExpectedPayments(string $tenantId, string $periodId): iterable;

    /**
     * Total payable balance for subledger reconciliation.
     *
     * @return object Balance projection
     */
    public function getTotalBalance(string $tenantId, string $periodId): object;

    /**
     * GL control account code for payable subledger.
     */
    public function getControlAccountCode(string $tenantId): ?string;

    /**
     * Unposted payable movements for reconciliation detail.
     *
     * @return iterable<int, object> Movement projections
     */
    public function getUnpostedTransactions(string $tenantId, string $periodId): iterable;
}
