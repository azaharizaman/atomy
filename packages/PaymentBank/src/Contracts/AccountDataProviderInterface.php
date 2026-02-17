<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\DTOs\BankAccount;
use Nexus\PaymentBank\DTOs\BankAccountBalance;
use Nexus\PaymentBank\DTOs\BankTransactionList;

interface AccountDataProviderInterface
{
    /**
     * Get accounts associated with the provided credentials.
     *
     * @param array<string, mixed> $credentials Provider-specific credentials
     * @return array<BankAccount> List of bank account DTOs
     */
    public function getAccounts(array $credentials): array;

    /**
     * Get a specific account.
     *
     * @param array<string, mixed> $credentials Provider-specific credentials
     * @param string $accountId Provider's account ID
     * @return BankAccount Bank account DTO
     */
    public function getAccount(array $credentials, string $accountId): BankAccount;

    /**
     * Get the balance for a specific account.
     *
     * @param array<string, mixed> $credentials Provider-specific credentials
     * @param string $accountId Provider's account ID
     * @return BankAccountBalance
     */
    public function getBalance(array $credentials, string $accountId): BankAccountBalance;

    /**
     * Get transactions for a specific account.
     *
     * @param array<string, mixed> $credentials Provider-specific credentials
     * @param string $accountId Provider's account ID
     * @param \DateTimeImmutable $startDate
     * @param \DateTimeImmutable $endDate
     * @param array<string, mixed> $options Pagination options (cursor, limit)
     * @return BankTransactionList
     */
    public function getTransactions(
        array $credentials,
        string $accountId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $options = []
    ): BankTransactionList;
}
