<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\VirtualCardStatus;

/**
 * Event dispatched when a virtual card is closed.
 */
final class VirtualCardClosedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $cardId
     * @param string $lastFour
     * @param VirtualCardStatus $previousStatus
     * @param string $closureReason
     * @param int $totalChargedCents
     * @param int $creditLimitCents
     * @param string $currency
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $cardId,
        public readonly string $lastFour,
        public readonly VirtualCardStatus $previousStatus,
        public readonly string $closureReason,
        public readonly int $totalChargedCents,
        public readonly int $creditLimitCents,
        public readonly string $currency,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::VIRTUAL_CARD, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'virtual_card.closed';
    }

    /**
     * Get the unused credit amount in cents.
     */
    public function getUnusedCreditCents(): int
    {
        return $this->creditLimitCents - $this->totalChargedCents;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'card_id' => $this->cardId,
            'last_four' => $this->lastFour,
            'previous_status' => $this->previousStatus->value,
            'closure_reason' => $this->closureReason,
            'total_charged_cents' => $this->totalChargedCents,
            'credit_limit_cents' => $this->creditLimitCents,
            'unused_credit_cents' => $this->getUnusedCreditCents(),
            'currency' => $this->currency,
        ]);
    }
}
