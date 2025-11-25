<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

use Nexus\Manufacturing\Enums\PlanningZone;

/**
 * Planning Horizon value object.
 *
 * Represents a configurable planning time window with zones.
 */
final readonly class PlanningHorizon
{
    /**
     * @param \DateTimeImmutable $startDate Planning start date
     * @param \DateTimeImmutable $endDate Planning end date
     * @param int $frozenDays Days in frozen zone
     * @param int $slushyDays Days in slushy zone
     * @param int $liquidDays Days in liquid zone
     * @param string $bucketSize Time bucket size: 'day', 'week', 'month'
     */
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public int $frozenDays = 14,
        public int $slushyDays = 14,
        public int $liquidDays = 62,
        public string $bucketSize = 'day',
    ) {
        if ($this->endDate <= $this->startDate) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
        if ($this->frozenDays < 0 || $this->slushyDays < 0 || $this->liquidDays < 0) {
            throw new \InvalidArgumentException('Zone days cannot be negative');
        }
        if (!in_array($this->bucketSize, ['day', 'week', 'month'], true)) {
            throw new \InvalidArgumentException('Bucket size must be day, week, or month');
        }
    }

    /**
     * Get total planning horizon in days.
     */
    public function getTotalDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days;
    }

    /**
     * Get the zone for a given date.
     */
    public function getZoneForDate(\DateTimeImmutable $date): PlanningZone
    {
        $dayOffset = (int) $this->startDate->diff($date)->days;

        if ($dayOffset < 0 || $date > $this->endDate) {
            return PlanningZone::LIQUID; // Outside horizon treated as liquid
        }

        if ($dayOffset < $this->frozenDays) {
            return PlanningZone::FROZEN;
        }

        if ($dayOffset < $this->frozenDays + $this->slushyDays) {
            return PlanningZone::SLUSHY;
        }

        return PlanningZone::LIQUID;
    }

    /**
     * Get frozen zone end date.
     */
    public function getFrozenEndDate(): \DateTimeImmutable
    {
        return $this->startDate->modify("+{$this->frozenDays} days");
    }

    /**
     * Get slushy zone end date.
     */
    public function getSlushyEndDate(): \DateTimeImmutable
    {
        $totalDays = $this->frozenDays + $this->slushyDays;
        return $this->startDate->modify("+{$totalDays} days");
    }

    /**
     * Get time buckets for the horizon.
     *
     * @return array<array{start: \DateTimeImmutable, end: \DateTimeImmutable, zone: PlanningZone}>
     */
    public function getBuckets(): array
    {
        $buckets = [];
        $current = $this->startDate;
        $interval = $this->getBucketInterval();

        while ($current < $this->endDate) {
            $bucketEnd = min(
                $current->modify($interval),
                $this->endDate
            );

            $buckets[] = [
                'start' => $current,
                'end' => $bucketEnd,
                'zone' => $this->getZoneForDate($current),
            ];

            $current = $bucketEnd;
        }

        return $buckets;
    }

    /**
     * Get bucket count.
     */
    public function getBucketCount(): int
    {
        return match ($this->bucketSize) {
            'day' => $this->getTotalDays(),
            'week' => (int) ceil($this->getTotalDays() / 7),
            'month' => (int) ceil($this->getTotalDays() / 30),
            default => $this->getTotalDays(),
        };
    }

    /**
     * Create a horizon for the next N days.
     */
    public static function forDays(
        int $days,
        int $frozenDays = 14,
        int $slushyDays = 14,
        string $bucketSize = 'day'
    ): self {
        $start = new \DateTimeImmutable('today');
        $end = $start->modify("+{$days} days");

        return new self(
            startDate: $start,
            endDate: $end,
            frozenDays: $frozenDays,
            slushyDays: $slushyDays,
            liquidDays: max(0, $days - $frozenDays - $slushyDays),
            bucketSize: $bucketSize,
        );
    }

    /**
     * Create a horizon for the next N weeks.
     */
    public static function forWeeks(
        int $weeks,
        int $frozenWeeks = 2,
        int $slushyWeeks = 2
    ): self {
        return self::forDays(
            days: $weeks * 7,
            frozenDays: $frozenWeeks * 7,
            slushyDays: $slushyWeeks * 7,
            bucketSize: 'week'
        );
    }

    /**
     * Create a horizon for the next N months.
     */
    public static function forMonths(
        int $months,
        int $frozenWeeks = 2,
        int $slushyWeeks = 2
    ): self {
        return self::forDays(
            days: $months * 30,
            frozenDays: $frozenWeeks * 7,
            slushyDays: $slushyWeeks * 7,
            bucketSize: 'month'
        );
    }

    /**
     * Get bucket interval string.
     */
    private function getBucketInterval(): string
    {
        return match ($this->bucketSize) {
            'day' => '+1 day',
            'week' => '+1 week',
            'month' => '+1 month',
            default => '+1 day',
        };
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
            'frozenDays' => $this->frozenDays,
            'slushyDays' => $this->slushyDays,
            'liquidDays' => $this->liquidDays,
            'bucketSize' => $this->bucketSize,
            'totalDays' => $this->getTotalDays(),
            'bucketCount' => $this->getBucketCount(),
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
            startDate: new \DateTimeImmutable($data['startDate']),
            endDate: new \DateTimeImmutable($data['endDate']),
            frozenDays: (int) ($data['frozenDays'] ?? 14),
            slushyDays: (int) ($data['slushyDays'] ?? 14),
            liquidDays: (int) ($data['liquidDays'] ?? 62),
            bucketSize: $data['bucketSize'] ?? 'day',
        );
    }
}
