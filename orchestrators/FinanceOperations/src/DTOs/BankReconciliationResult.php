<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for bank reconciliation operations.
 */
final readonly class BankReconciliationResult
{
    public function __construct(
        public bool $success,
        public string $bankAccountId,
        public float $bookBalance,
        public float $bankBalance,
        public float $difference,
        public array $matchedTransactions = [],
        public array $unmatchedItems = [],
        public ?string $errorMessage = null,
    ) {}
}
