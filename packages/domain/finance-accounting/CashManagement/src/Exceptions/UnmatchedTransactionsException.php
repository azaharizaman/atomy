<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Unmatched Transactions Exception
 *
 * Thrown when critical transactions cannot be matched during reconciliation.
 */
class UnmatchedTransactionsException extends RuntimeException
{
    /**
     * @param array<string> $transactionIds
     */
    public function __construct(
        string $message = 'Unmatched transactions found',
        private readonly array $transactionIds = [],
        private readonly ?string $totalAmount = null
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string>
     */
    public function getTransactionIds(): array
    {
        return $this->transactionIds;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    /**
     * @param array<string> $transactionIds
     */
    public static function withDetails(array $transactionIds, string $totalAmount): self
    {
        return new self(
            message: sprintf(
                '%d transaction(s) could not be matched (Total: %s)',
                count($transactionIds),
                $totalAmount
            ),
            transactionIds: $transactionIds,
            totalAmount: $totalAmount
        );
    }
}
