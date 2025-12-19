<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;

/**
 * Base class for payment domain events.
 */
abstract class PaymentEvent
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
