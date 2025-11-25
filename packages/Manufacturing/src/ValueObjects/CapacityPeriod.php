<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * Capacity Period value object.
 *
 * Represents capacity for a single time period (day/week/month).
 */
final readonly class CapacityPeriod
{
    /**
     * @param \DateTimeImmutable $periodStart Period start date
     * @param \DateTimeImmutable $periodEnd Period end date
     * @param float $availableHours Available capacity hours
     * @param float $loadedHours Loaded/committed hours
     * @param array<CapacityLoad> $loads Individual capacity loads
     */
    public function __construct(
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public float $availableHours = 0.0,
        public float $loadedHours = 0.0,
        public array $loads = [],
    ) {
        if ($this->periodEnd <= $this->periodStart) {
            throw new \InvalidArgumentException('Period end must be after period start');
        }
        if ($this->availableHours < 0 || $this->loadedHours < 0) {
            throw new \InvalidArgumentException('Hours cannot be negative');
        }
    }

    /**
     * Get utilization percentage.
     */
    public function getUtilization(): float
    {
        if ($this->availableHours <= 0) {
            return $this->loadedHours > 0 ? 100.0 : 0.0;
        }
        return ($this->loadedHours / $this->availableHours) * 100;
    }

    /**
     * Check if period is overloaded.
     */
    public function isOverloaded(): bool
    {
        return $this->loadedHours > $this->availableHours;
    }

    /**
     * Get remaining capacity hours.
     */
    public function getRemainingHours(): float
    {
        return max(0, $this->availableHours - $this->loadedHours);
    }

    /**
     * Get excess load hours.
     */
    public function getExcessHours(): float
    {
        return max(0, $this->loadedHours - $this->availableHours);
    }

    /**
     * Check if capacity is available.
     */
    public function hasCapacity(float $requiredHours = 0.0): bool
    {
        return $this->getRemainingHours() >= $requiredHours;
    }

    /**
     * Get period duration in days.
     */
    public function getDurationDays(): int
    {
        return (int) $this->periodStart->diff($this->periodEnd)->days;
    }

    /**
     * Get period label (e.g., "2024-W01" for week, "2024-01" for month).
     */
    public function getLabel(): string
    {
        $days = $this->getDurationDays();

        if ($days <= 1) {
            return $this->periodStart->format('Y-m-d');
        }

        if ($days <= 7) {
            return $this->periodStart->format('Y-\\WW');
        }

        return $this->periodStart->format('Y-m');
    }

    /**
     * Create a copy with updated loaded hours.
     */
    public function withLoadedHours(float $hours): self
    {
        return new self(
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            availableHours: $this->availableHours,
            loadedHours: $hours,
            loads: $this->loads,
        );
    }

    /**
     * Create a copy with additional load.
     */
    public function withAdditionalLoad(CapacityLoad $load): self
    {
        return new self(
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            availableHours: $this->availableHours,
            loadedHours: $this->loadedHours + $load->hours,
            loads: [...$this->loads, $load],
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'periodStart' => $this->periodStart->format('Y-m-d'),
            'periodEnd' => $this->periodEnd->format('Y-m-d'),
            'label' => $this->getLabel(),
            'availableHours' => $this->availableHours,
            'loadedHours' => $this->loadedHours,
            'utilization' => $this->getUtilization(),
            'remainingHours' => $this->getRemainingHours(),
            'isOverloaded' => $this->isOverloaded(),
            'loads' => array_map(
                fn (CapacityLoad $load) => $load->toArray(),
                $this->loads
            ),
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            periodStart: new \DateTimeImmutable($data['periodStart']),
            periodEnd: new \DateTimeImmutable($data['periodEnd']),
            availableHours: (float) ($data['availableHours'] ?? 0.0),
            loadedHours: (float) ($data['loadedHours'] ?? 0.0),
            loads: array_map(
                fn (array $load) => CapacityLoad::fromArray($load),
                $data['loads'] ?? []
            ),
        );
    }
}
