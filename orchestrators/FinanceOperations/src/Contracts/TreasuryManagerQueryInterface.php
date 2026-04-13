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

    /**
     * Get bank account by ID with GL account mapping.
     *
     * @param string $tenantId Tenant identifier
     * @param string $bankAccountId Bank account identifier
     * @return BankAccountProjectionInterface|null Bank account projection with GL account code
     */
    public function getBankAccountById(string $tenantId, string $bankAccountId): ?BankAccountProjectionInterface;
}
