<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Request DTO for cash position operations.
 *
 * Used to query the current cash position across bank accounts,
 * supporting single account or consolidated views.
 *
 * @since 1.0.0
 */
final readonly class CashPositionRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string|null $bankAccountId Specific bank account ID, or null for all accounts
     * @param \DateTimeImmutable|null $asOfDate Date for position calculation, defaults to now
     * @param string $currency Currency for consolidated reporting (default: USD)
     */
    public function __construct(
        public string $tenantId,
        public ?string $bankAccountId = null,
        public ?\DateTimeImmutable $asOfDate = null,
        public string $currency = 'USD',
    ) {}
}
