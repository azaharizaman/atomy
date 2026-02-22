<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Result DTO for cash position operations.
 *
 * Contains cash position data across one or more bank accounts,
 * with consolidated balance reporting.
 *
 * @since 1.0.0
 */
final readonly class CashPositionResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string $tenantId Tenant identifier
     * @param array<int, array{accountId: string, accountName: string, balance: string, currency: string}> $positions Array of cash position data per account
     * @param string|null $consolidatedBalance Consolidated balance in reporting currency
     * @param string $currency Reporting currency code
     * @param \DateTimeImmutable $asOfDate Date of the position
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $tenantId,
        public array $positions = [],
        public ?string $consolidatedBalance = null,
        public string $currency = 'USD',
        public \DateTimeImmutable $asOfDate = new \DateTimeImmutable(),
        public ?string $error = null,
    ) {}
}
