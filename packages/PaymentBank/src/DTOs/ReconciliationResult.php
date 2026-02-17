<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\PaymentBank\Contracts\ReconciliationResultInterface;

final readonly class ReconciliationResult implements ReconciliationResultInterface
{
    public function __construct(
        private array $matchedTransactions,
        private array $unmatchedTransactions
    ) {}

    public function getMatchedTransactions(): array
    {
        return $this->matchedTransactions;
    }

    public function getUnmatchedTransactions(): array
    {
        return $this->unmatchedTransactions;
    }
}
