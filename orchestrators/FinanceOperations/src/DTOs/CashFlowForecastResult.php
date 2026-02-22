<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for cash flow forecast operations.
 */
final readonly class CashFlowForecastResult
{
    /**
     * @param array<string, mixed> $forecast
     */
    public function __construct(
        public bool $success,
        public string $tenantId,
        public string $bankAccountId,
        public array $forecast,
        public \DateTimeImmutable $generatedAt,
        public ?string $errorMessage = null,
    ) {}
}
