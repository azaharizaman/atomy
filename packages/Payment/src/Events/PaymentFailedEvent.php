<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a payment fails.
 */
final class PaymentFailedEvent extends PaymentEvent
{
    public function __construct(
        string $paymentId,
        string $tenantId,
        public readonly Money $amount,
        public readonly string $failureCode,
        public readonly string $failureMessage,
        public readonly bool $isRetryable,
        public readonly int $attemptCount,
        public readonly ?\DateTimeImmutable $nextRetryAt,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($paymentId, $tenantId, $occurredAt);
    }

    /**
     * Check if the failure is permanent.
     */
    public function isPermanent(): bool
    {
        return !$this->isRetryable;
    }
}
