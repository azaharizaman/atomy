<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Result DTO for bank reconciliation operations.
 *
 * Contains reconciliation results including matched and unmatched
 * transactions, and any discrepancies found.
 *
 * @since 1.0.0
 */
final readonly class BankReconciliationResult
{
    /**
     * @param bool $success Whether the reconciliation succeeded
     * @param string $bankAccountId Bank account that was reconciled
     * @param int $matchedTransactions Number of successfully matched transactions
     * @param int $unmatchedTransactions Number of unmatched transactions
     * @param array<int, array{type: string, amount: string, date: string, description: string, reason: string}> $discrepancies List of discrepancies found
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $bankAccountId,
        public int $matchedTransactions = 0,
        public int $unmatchedTransactions = 0,
        public array $discrepancies = [],
        public ?string $error = null,
    ) {}
}
