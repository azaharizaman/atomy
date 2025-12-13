<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when multi-entity payment batch is created.
 */
final readonly class MultiEntityPaymentBatchCreatedEvent
{
    public function __construct(
        public string $batchId,
        public string $entityId,
        public string $entityName,
        public int $paymentCount,
        public Money $totalAmount,
        public string $paymentMethod,
        public string $createdBy,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
