<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class BankTransactionList
{
    /**
     * @param array<mixed> $transactions
     */
    public function __construct(
        public array $transactions,
        public ?string $nextCursor = null
    ) {}
}
