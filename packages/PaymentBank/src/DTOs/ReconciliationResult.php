<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\PaymentBank\Contracts\ReconciliationResultInterface;

final class ReconciliationResult implements ReconciliationResultInterface
{
    public function __construct(
        private readonly array $matchedTransactions,
        private readonly array $unmatchedTransactions
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
