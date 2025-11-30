<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Enums\LotSizingStrategy;

/**
 * Event raised when MRP calculation is completed.
 */
final readonly class MrpCalculatedEvent
{
    /**
     * @param array<string, mixed> $summary
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $mrpRunId,
        public \DateTimeImmutable $planningStartDate,
        public \DateTimeImmutable $planningEndDate,
        public int $itemsProcessed,
        public int $plannedOrdersGenerated,
        public int $exceptionsRaised,
        public LotSizingStrategy $lotSizingStrategy,
        public array $summary,
        public array $parameters,
        public float $executionTimeSeconds,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'mrp.calculated',
            'mrpRunId' => $this->mrpRunId,
            'planningStartDate' => $this->planningStartDate->format('c'),
            'planningEndDate' => $this->planningEndDate->format('c'),
            'itemsProcessed' => $this->itemsProcessed,
            'plannedOrdersGenerated' => $this->plannedOrdersGenerated,
            'exceptionsRaised' => $this->exceptionsRaised,
            'lotSizingStrategy' => $this->lotSizingStrategy->value,
            'summary' => $this->summary,
            'parameters' => $this->parameters,
            'executionTimeSeconds' => $this->executionTimeSeconds,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Check if MRP run had exceptions.
     */
    public function hasExceptions(): bool
    {
        return $this->exceptionsRaised > 0;
    }

    /**
     * Get processing rate (items per second).
     */
    public function getProcessingRate(): float
    {
        if ($this->executionTimeSeconds <= 0) {
            return 0.0;
        }

        return $this->itemsProcessed / $this->executionTimeSeconds;
    }
}
