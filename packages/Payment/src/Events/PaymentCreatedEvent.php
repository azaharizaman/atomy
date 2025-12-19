<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;

/**
 * Event dispatched when a new payment is created.
 */
final class PaymentCreatedEvent extends PaymentEvent
{
    public function __construct(
        string $paymentId,
        string $tenantId,
        public readonly string $reference,
        public readonly PaymentDirection $direction,
        public readonly Money $amount,
        public readonly PaymentMethodType $methodType,
        public readonly ?string $payerId,
        public readonly ?string $payeeId,
        public readonly ?string $idempotencyKey,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($paymentId, $tenantId, $occurredAt);
    }
}
