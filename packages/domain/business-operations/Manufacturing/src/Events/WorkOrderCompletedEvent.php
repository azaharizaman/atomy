<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

/**
 * Event raised when a work order is completed.
 */
final readonly class WorkOrderCompletedEvent
{
    public function __construct(
        public string $workOrderId,
        public string $number,
        public string $productId,
        public float $plannedQuantity,
        public float $completedQuantity,
        public float $scrapQuantity,
        public string $uomCode,
        public \DateTimeImmutable $plannedStartDate,
        public \DateTimeImmutable $plannedEndDate,
        public ?\DateTimeImmutable $actualStartDate,
        public \DateTimeImmutable $actualEndDate,
        public float $plannedHours,
        public float $actualHours,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'work_order.completed',
            'workOrderId' => $this->workOrderId,
            'number' => $this->number,
            'productId' => $this->productId,
            'plannedQuantity' => $this->plannedQuantity,
            'completedQuantity' => $this->completedQuantity,
            'scrapQuantity' => $this->scrapQuantity,
            'uomCode' => $this->uomCode,
            'plannedStartDate' => $this->plannedStartDate->format('c'),
            'plannedEndDate' => $this->plannedEndDate->format('c'),
            'actualStartDate' => $this->actualStartDate?->format('c'),
            'actualEndDate' => $this->actualEndDate->format('c'),
            'plannedHours' => $this->plannedHours,
            'actualHours' => $this->actualHours,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Get yield percentage.
     */
    public function getYieldPercentage(): float
    {
        if ($this->plannedQuantity <= 0) {
            return 0.0;
        }

        return ($this->completedQuantity / $this->plannedQuantity) * 100;
    }

    /**
     * Get scrap percentage.
     */
    public function getScrapPercentage(): float
    {
        $totalOutput = $this->completedQuantity + $this->scrapQuantity;

        if ($totalOutput <= 0) {
            return 0.0;
        }

        return ($this->scrapQuantity / $totalOutput) * 100;
    }

    /**
     * Get efficiency percentage.
     */
    public function getEfficiencyPercentage(): float
    {
        if ($this->actualHours <= 0) {
            return 0.0;
        }

        return ($this->plannedHours / $this->actualHours) * 100;
    }

    /**
     * Check if completed on time.
     */
    public function isOnTime(): bool
    {
        return $this->actualEndDate <= $this->plannedEndDate;
    }

    /**
     * Check if quantity target was met.
     */
    public function isQuantityTargetMet(): bool
    {
        return $this->completedQuantity >= $this->plannedQuantity;
    }
}
