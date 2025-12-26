<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\DTOs\BankAccountBalance;
use Nexus\PaymentBank\DTOs\BankTransactionList;
use Nexus\PaymentBank\Entities\BankConnectionInterface;

interface AccountDataProviderInterface
{
    /**
     * Get accounts associated with a connection.
     *
     * @param BankConnectionInterface $connection
     * @return array<array<string, mixed>> List of accounts (raw data or DTOs)
     */
    public function getAccounts(BankConnectionInterface $connection): array;

    /**
     * Get the balance for a specific account.
     *
     * @param BankConnectionInterface $connection
     * @param string $accountId Provider's account ID
     * @return BankAccountBalance
     */
    public function getBalance(BankConnectionInterface $connection, string $accountId): BankAccountBalance;

    /**
     * Get transactions for a specific account.
     *
     * @param BankConnectionInterface $connection
     * @param string $accountId Provider's account ID
     * @param \DateTimeImmutable $startDate
     * @param \DateTimeImmutable $endDate
     * @param array<string, mixed> $options Pagination options (cursor, limit)
     * @return BankTransactionList
     */
    public function getTransactions(
        BankConnectionInterface $connection,
        string $accountId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $options = []
    ): BankTransactionList;
}
