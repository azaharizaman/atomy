<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\VirtualCardType;

/**
 * Event dispatched when a virtual card is created.
 */
final class VirtualCardCreatedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $cardId
     * @param VirtualCardType $cardType
     * @param string $lastFour
     * @param int $creditLimitCents
     * @param string $currency
     * @param \DateTimeImmutable $expiresAt
     * @param string|null $vendorId
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $cardId,
        public readonly VirtualCardType $cardType,
        public readonly string $lastFour,
        public readonly int $creditLimitCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly ?string $vendorId,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::VIRTUAL_CARD, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'virtual_card.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'card_id' => $this->cardId,
            'card_type' => $this->cardType->value,
            'last_four' => $this->lastFour,
            'credit_limit_cents' => $this->creditLimitCents,
            'currency' => $this->currency,
            'expires_at' => $this->expiresAt->format(\DateTimeInterface::RFC3339),
            'vendor_id' => $this->vendorId,
        ]);
    }
}
