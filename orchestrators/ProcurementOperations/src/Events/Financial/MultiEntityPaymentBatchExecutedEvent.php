<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when multi-entity payment batch is executed.
 */
final readonly class MultiEntityPaymentBatchExecutedEvent
{
    public function __construct(
        public string $batchId,
        public string $entityId,
        public int $successfulPayments,
        public int $failedPayments,
        public Money $totalPaid,
        public Money $totalFailed,
        public string $executedBy,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Get total payments attempted.
     */
    public function getTotalPayments(): int
    {
        return $this->successfulPayments + $this->failedPayments;
    }

    /**
     * Get success rate.
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalPayments();
        if ($total === 0) {
            return 100.0;
        }

        return round(($this->successfulPayments / $total) * 100, 2);
    }
}
