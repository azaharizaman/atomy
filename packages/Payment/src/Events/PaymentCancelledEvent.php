<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a payment is cancelled.
 */
final class PaymentCancelledEvent extends PaymentEvent
{
    public function __construct(
        string $paymentId,
        string $tenantId,
        public readonly Money $amount,
        public readonly ?string $reason,
        public readonly ?string $cancelledBy,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($paymentId, $tenantId, $occurredAt);
    }
}
