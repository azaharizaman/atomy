<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when intercompany settlement is completed.
 */
final readonly class IntercompanySettlementCompletedEvent
{
    public function __construct(
        public string $settlementId,
        public string $fromEntityId,
        public string $toEntityId,
        public Money $settlementAmount,
        public string $settlementCurrency,
        public string $paymentReference,
        public \DateTimeImmutable $settledAt,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
