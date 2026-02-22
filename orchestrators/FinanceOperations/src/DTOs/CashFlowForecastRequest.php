<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for cash flow forecast operations.
 */
final readonly class CashFlowForecastRequest
{
    public function __construct(
        public string $tenantId,
        public string $bankAccountId,
        public \DateTimeImmutable $startDate,
        public int $daysAhead = 30,
        public array $options = [],
    ) {}
}
