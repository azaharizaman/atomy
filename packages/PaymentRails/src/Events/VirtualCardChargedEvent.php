<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when a charge is made on a virtual card.
 */
final class VirtualCardChargedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $cardId
     * @param string $lastFour
     * @param int $chargeAmountCents
     * @param string $currency
     * @param string $merchantName
     * @param string|null $merchantId
     * @param int $remainingCreditCents
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $cardId,
        public readonly string $lastFour,
        public readonly int $chargeAmountCents,
        public readonly string $currency,
        public readonly string $merchantName,
        public readonly ?string $merchantId,
        public readonly int $remainingCreditCents,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::VIRTUAL_CARD, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'virtual_card.charged';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'card_id' => $this->cardId,
            'last_four' => $this->lastFour,
            'charge_amount_cents' => $this->chargeAmountCents,
            'currency' => $this->currency,
            'merchant_name' => $this->merchantName,
            'merchant_id' => $this->merchantId,
            'remaining_credit_cents' => $this->remainingCreditCents,
        ]);
    }
}
