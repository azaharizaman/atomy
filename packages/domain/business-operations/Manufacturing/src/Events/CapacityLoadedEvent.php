<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

/**
 * Event raised when capacity is loaded to a work center.
 */
final readonly class CapacityLoadedEvent
{
    /**
     * @param array<string, mixed> $loadDetails
     */
    public function __construct(
        public string $workCenterId,
        public string $workCenterCode,
        public string $sourceType,
        public string $sourceId,
        public float $loadedHours,
        public float $setupHours,
        public float $runHours,
        public \DateTimeImmutable $loadDate,
        public float $previousUtilization,
        public float $newUtilization,
        public bool $isOverloaded,
        public array $loadDetails,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'capacity.loaded',
            'workCenterId' => $this->workCenterId,
            'workCenterCode' => $this->workCenterCode,
            'sourceType' => $this->sourceType,
            'sourceId' => $this->sourceId,
            'loadedHours' => $this->loadedHours,
            'setupHours' => $this->setupHours,
            'runHours' => $this->runHours,
            'loadDate' => $this->loadDate->format('c'),
            'previousUtilization' => $this->previousUtilization,
            'newUtilization' => $this->newUtilization,
            'isOverloaded' => $this->isOverloaded,
            'loadDetails' => $this->loadDetails,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Get the change in utilization.
     */
    public function getUtilizationChange(): float
    {
        return $this->newUtilization - $this->previousUtilization;
    }

    /**
     * Check if this load caused the overload.
     */
    public function causedOverload(): bool
    {
        return $this->isOverloaded && $this->previousUtilization < 100.0;
    }
}
