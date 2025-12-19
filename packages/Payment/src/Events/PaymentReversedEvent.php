<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a payment is reversed.
 */
final class PaymentReversedEvent extends PaymentEvent
{
    public function __construct(
        string $paymentId,
        string $tenantId,
        public readonly Money $originalAmount,
        public readonly Money $reversedAmount,
        public readonly ?string $reason,
        public readonly ?string $reversalTransactionId,
        public readonly ?string $reversedBy,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($paymentId, $tenantId, $occurredAt);
    }

    /**
     * Check if this was a partial reversal.
     */
    public function isPartialReversal(): bool
    {
        return !$this->originalAmount->equals($this->reversedAmount);
    }
}
