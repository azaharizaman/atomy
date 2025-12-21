<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\AchReturnCode;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when an ACH entry is returned.
 */
final class AchReturnReceivedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $traceNumber
     * @param AchReturnCode $returnCode
     * @param int $originalAmountCents
     * @param string $originalAccountNumber Last 4 digits
     * @param bool $isRetriable
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $traceNumber,
        public readonly AchReturnCode $returnCode,
        public readonly int $originalAmountCents,
        public readonly string $originalAccountNumber,
        public readonly bool $isRetriable,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::ACH, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'ach.return.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'trace_number' => $this->traceNumber,
            'return_code' => $this->returnCode->value,
            'return_description' => $this->returnCode->getDescription(),
            'original_amount_cents' => $this->originalAmountCents,
            'original_account_last4' => $this->originalAccountNumber,
            'is_retriable' => $this->isRetriable,
        ]);
    }
}
