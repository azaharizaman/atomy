<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when accrual reconciliation with GL has been completed.
 */
final readonly class AccrualReconciliationCompletedEvent
{
    public function __construct(
        public string $tenantId,
        public \DateTimeImmutable $asOfDate,
        public Money $glBalance,
        public Money $subledgerBalance,
        public Money $variance,
        public bool $isReconciled,
        public string $reconciledBy,
    ) {}
}
