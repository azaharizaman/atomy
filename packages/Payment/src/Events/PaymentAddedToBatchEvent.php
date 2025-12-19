<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a payment is added to a settlement batch.
 */
final class PaymentAddedToBatchEvent extends SettlementBatchEvent
{
    public function __construct(
        string $batchId,
        string $tenantId,
        string $processorId,
        public readonly string $paymentId,
        public readonly Money $amount,
        public readonly Money $fee,
        DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($batchId, $tenantId, $processorId, $occurredAt);
    }
}
