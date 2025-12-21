<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\NocCode;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when an ACH Notification of Change is received.
 */
final class AchNocReceivedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $traceNumber
     * @param NocCode $nocCode
     * @param string $correctedData
     * @param \DateTimeImmutable $effectiveDate
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $traceNumber,
        public readonly NocCode $nocCode,
        public readonly string $correctedData,
        public readonly \DateTimeImmutable $effectiveDate,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::ACH, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'ach.noc.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'trace_number' => $this->traceNumber,
            'noc_code' => $this->nocCode->value,
            'noc_description' => $this->nocCode->getDescription(),
            'corrected_data' => $this->correctedData,
            'effective_date' => $this->effectiveDate->format('Y-m-d'),
            'update_deadline' => $this->effectiveDate->modify('+6 days')->format('Y-m-d'),
        ]);
    }
}
