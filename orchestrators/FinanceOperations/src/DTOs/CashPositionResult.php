<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for cash position operations.
 */
final readonly class CashPositionResult
{
    /**
     * @param array<string, mixed> $balances
     */
    public function __construct(
        public bool $success,
        public string $bankAccountId,
        public string $balance,
        public string $currency,
        public \DateTimeImmutable $asOfDate,
        public array $balances = [],
        public ?string $errorMessage = null,
    ) {}
}
