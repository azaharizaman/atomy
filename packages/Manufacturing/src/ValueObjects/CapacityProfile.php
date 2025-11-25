<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

use Nexus\Manufacturing\Enums\PlanningZone;

/**
 * Capacity Profile value object.
 *
 * Represents capacity load profile for a work center over time.
 */
final readonly class CapacityProfile
{
    /**
     * @param string $workCenterId Work center ID
     * @param PlanningHorizon $horizon Planning horizon
     * @param array<CapacityPeriod> $periods Capacity by period
     * @param float $totalAvailableCapacity Total available hours
     * @param float $totalLoadedCapacity Total loaded/committed hours
     * @param \DateTimeImmutable $calculatedAt Calculation timestamp
     */
    public function __construct(
        public string $workCenterId,
        public PlanningHorizon $horizon,
        public array $periods = [],
        public float $totalAvailableCapacity = 0.0,
        public float $totalLoadedCapacity = 0.0,
        public ?\DateTimeImmutable $calculatedAt = null,
    ) {
    }

    /**
     * Get overall utilization percentage.
     */
    public function getUtilization(): float
    {
        if ($this->totalAvailableCapacity <= 0) {
            return 0.0;
        }
        return ($this->totalLoadedCapacity / $this->totalAvailableCapacity) * 100;
    }

    /**
     * Check if work center is overloaded.
     */
    public function isOverloaded(): bool
    {
        return $this->totalLoadedCapacity > $this->totalAvailableCapacity;
    }

    /**
     * Get excess load hours.
     */
    public function getExcessLoad(): float
    {
        return max(0, $this->totalLoadedCapacity - $this->totalAvailableCapacity);
    }

    /**
     * Get available capacity remaining.
     */
    public function getAvailableCapacity(): float
    {
        return max(0, $this->totalAvailableCapacity - $this->totalLoadedCapacity);
    }

    /**
     * Get overloaded periods.
     *
     * @return array<CapacityPeriod>
     */
    public function getOverloadedPeriods(): array
    {
        return array_filter(
            $this->periods,
            fn (CapacityPeriod $period) => $period->isOverloaded()
        );
    }

    /**
     * Get periods by zone.
     *
     * @return array<CapacityPeriod>
     */
    public function getPeriodsByZone(PlanningZone $zone): array
    {
        return array_filter(
            $this->periods,
            fn (CapacityPeriod $period) =>
                $this->horizon->getZoneForDate($period->periodStart) === $zone
        );
    }

    /**
     * Get peak utilization percentage.
     */
    public function getPeakUtilization(): float
    {
        if (count($this->periods) === 0) {
            return 0.0;
        }

        return max(array_map(
            fn (CapacityPeriod $period) => $period->getUtilization(),
            $this->periods
        ));
    }

    /**
     * Get average utilization percentage.
     */
    public function getAverageUtilization(): float
    {
        if (count($this->periods) === 0) {
            return 0.0;
        }

        $sum = array_sum(array_map(
            fn (CapacityPeriod $period) => $period->getUtilization(),
            $this->periods
        ));

        return $sum / count($this->periods);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workCenterId' => $this->workCenterId,
            'horizon' => $this->horizon->toArray(),
            'periods' => array_map(
                fn (CapacityPeriod $period) => $period->toArray(),
                $this->periods
            ),
            'totalAvailableCapacity' => $this->totalAvailableCapacity,
            'totalLoadedCapacity' => $this->totalLoadedCapacity,
            'calculatedAt' => $this->calculatedAt?->format('Y-m-d H:i:s'),
            'summary' => [
                'utilization' => $this->getUtilization(),
                'isOverloaded' => $this->isOverloaded(),
                'excessLoad' => $this->getExcessLoad(),
                'availableCapacity' => $this->getAvailableCapacity(),
                'peakUtilization' => $this->getPeakUtilization(),
                'averageUtilization' => $this->getAverageUtilization(),
                'overloadedPeriodCount' => count($this->getOverloadedPeriods()),
            ],
        ];
    }
}
