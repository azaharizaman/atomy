<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when accrual aging threshold is exceeded.
 */
final readonly class AccrualAgingAlertEvent
{
    public function __construct(
        public string $tenantId,
        public string $agingBucket,
        public Money $amount,
        public Money $threshold,
        public \DateTimeImmutable $periodEndDate,
    ) {}
}
