<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\WireType;

/**
 * Event dispatched when a wire transfer is initiated.
 */
final class WireTransferInitiatedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param WireType $wireType
     * @param int $amountCents
     * @param string $currency
     * @param string $beneficiaryName
     * @param string|null $beneficiaryCountry
     * @param bool $isUrgent
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly WireType $wireType,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly string $beneficiaryName,
        public readonly ?string $beneficiaryCountry,
        public readonly bool $isUrgent,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::WIRE, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'wire.transfer.initiated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'wire_type' => $this->wireType->value,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'beneficiary_name' => $this->beneficiaryName,
            'beneficiary_country' => $this->beneficiaryCountry,
            'is_urgent' => $this->isUrgent,
        ]);
    }
}
