<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\WireType;

/**
 * Event dispatched when a wire transfer is confirmed.
 */
final class WireTransferConfirmedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param WireType $wireType
     * @param string $confirmationNumber
     * @param string|null $federalReferenceNumber
     * @param string|null $swiftReferenceNumber
     * @param int $amountCents
     * @param string $currency
     * @param int|null $feeCents
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly WireType $wireType,
        public readonly string $confirmationNumber,
        public readonly ?string $federalReferenceNumber,
        public readonly ?string $swiftReferenceNumber,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly ?int $feeCents,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::WIRE, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'wire.transfer.confirmed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'wire_type' => $this->wireType->value,
            'confirmation_number' => $this->confirmationNumber,
            'federal_reference_number' => $this->federalReferenceNumber,
            'swift_reference_number' => $this->swiftReferenceNumber,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'fee_cents' => $this->feeCents,
        ]);
    }
}
