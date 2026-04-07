<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract for treasury read operations used by orchestrators.
 */
interface TreasuryManagerQueryInterface
{
    /**
     * Get point-in-time cash position for a bank account.
     *
     * @return object Position projection
     */
    public function getPosition(string $tenantId, string $bankAccountId): object;

    /**
     * Get statement lines for reconciliation.
     *
     * @return iterable<int, object> Statement-line projections
     */
    public function getStatementLines(string $tenantId, string $bankAccountId): iterable;

    /**
     * Get tenant bank accounts.
     *
     * @return iterable<int, object> Bank-account projections
     */
    public function getBankAccounts(string $tenantId): iterable;
}
