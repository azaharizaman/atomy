<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\DTOs\BankAccount;
use Nexus\PaymentBank\DTOs\BankTransaction;
use Nexus\Common\ValueObjects\Period;

interface AccountServiceInterface
{
    /**
     * Get all accounts for a specific connection.
     *
     * @param string $connectionId
     * @return array<BankAccount>
     */
    public function getAccounts(string $connectionId): array;

    /**
     * Get a specific account details.
     *
     * @param string $connectionId
     * @param string $accountId
     * @return BankAccount
     */
    public function getAccount(string $connectionId, string $accountId): BankAccount;

    /**
     * Get transactions for an account within a period.
     *
     * @param string $connectionId
     * @param string $accountId
     * @param Period $period
     * @return array<BankTransaction>
     */
    public function getTransactions(string $connectionId, string $accountId, Period $period): array;
}
