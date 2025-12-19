<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a payment starts processing.
 */
final class PaymentProcessingEvent extends PaymentEvent
{
    public function __construct(
        string $paymentId,
        string $tenantId,
        public readonly Money $amount,
        public readonly string $executorName,
        public readonly ?string $providerTransactionId,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($paymentId, $tenantId, $occurredAt);
    }
}
