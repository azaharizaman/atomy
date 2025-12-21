<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\RailType;

/**
 * Base class for all payment rail events.
 */
abstract class PaymentRailEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    /**
     * @param string $transactionId
     * @param RailType $railType
     * @param string $tenantId
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly RailType $railType,
        public readonly string $tenantId,
        public readonly array $metadata = [],
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    /**
     * Get the event name.
     */
    abstract public function getEventName(): string;

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'transaction_id' => $this->transactionId,
            'rail_type' => $this->railType->value,
            'tenant_id' => $this->tenantId,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
            'metadata' => $this->metadata,
        ];
    }
}
