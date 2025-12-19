<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a payment is successfully completed.
 */
final class PaymentCompletedEvent extends PaymentEvent
{
    public function __construct(
        string $paymentId,
        string $tenantId,
        public readonly Money $amount,
        public readonly Money $settledAmount,
        public readonly ?string $providerTransactionId,
        public readonly \DateTimeImmutable $completedAt,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($paymentId, $tenantId, $occurredAt);
    }

    /**
     * Check if there was a settlement difference (e.g., currency conversion).
     */
    public function hasSettlementDifference(): bool
    {
        return !$this->amount->equals($this->settledAmount);
    }
}
