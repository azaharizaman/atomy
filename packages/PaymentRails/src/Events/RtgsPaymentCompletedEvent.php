<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\RtgsSystem;

/**
 * Event dispatched when an RTGS payment is completed.
 */
final class RtgsPaymentCompletedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param RtgsSystem $rtgsSystem
     * @param int $amountCents
     * @param string $currency
     * @param string $confirmationNumber
     * @param \DateTimeImmutable $settledAt
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly RtgsSystem $rtgsSystem,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly string $confirmationNumber,
        public readonly \DateTimeImmutable $settledAt,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::RTGS, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'rtgs.payment.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rtgs_system' => $this->rtgsSystem->value,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'confirmation_number' => $this->confirmationNumber,
            'settled_at' => $this->settledAt->format(\DateTimeInterface::RFC3339),
        ]);
    }
}
