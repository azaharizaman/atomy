<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\DTOs\RailSelectionCriteria;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when a payment rail is selected.
 */
final class RailSelected
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly RailType $railType,
        public readonly RailSelectionCriteria $criteria,
        public readonly float $score,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getEventName(): string
    {
        return 'rail.selected';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'rail_type' => $this->railType->value,
            'score' => $this->score,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
            'criteria' => $this->criteria->toArray(),
        ];
    }
}
