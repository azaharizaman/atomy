<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for cash position operations.
 */
final readonly class CashPositionRequest
{
    public function __construct(
        public string $tenantId,
        public string $bankAccountId,
        public ?\DateTimeImmutable $asOfDate = null,
    ) {}
}
