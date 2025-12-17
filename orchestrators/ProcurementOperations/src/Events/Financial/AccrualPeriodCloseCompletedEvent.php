<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when accrual period close has been completed.
 */
final readonly class AccrualPeriodCloseCompletedEvent
{
    public function __construct(
        public string $tenantId,
        public \DateTimeImmutable $periodEndDate,
        public int $totalOpenAccruals,
        public Money $totalOpenAmount,
        public int $adjustmentsPosted,
        public string $closedBy,
        public \DateTimeImmutable $closedAt,
    ) {}
}
