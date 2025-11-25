<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\ValueObjects;

use Nexus\Manufacturing\Enums\PlanningZone;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\Tests\TestCase;

final class PlanningHorizonTest extends TestCase
{
    public function testCreatePlanningHorizon(): void
    {
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-03-31');

        $horizon = new PlanningHorizon(
            startDate: $start,
            endDate: $end,
            frozenDays: 14,
            slushyDays: 14,
            liquidDays: 62,
            bucketSize: 'week',
        );

        $this->assertSame($start, $horizon->startDate);
        $this->assertSame($end, $horizon->endDate);
        $this->assertSame(14, $horizon->frozenDays);
        $this->assertSame(14, $horizon->slushyDays);
        $this->assertSame(62, $horizon->liquidDays);
        $this->assertSame('week', $horizon->bucketSize);
    }

    public function testGetTotalDays(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
        );

        $this->assertSame(30, $horizon->getTotalDays());
    }

    public function testGetBucketsDaily(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-08'),
            bucketSize: 'day',
        );

        $buckets = $horizon->getBuckets();

        $this->assertCount(7, $buckets);
        $this->assertSame('2024-01-01', $buckets[0]['start']->format('Y-m-d'));
    }

    public function testGetBucketsWeekly(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-29'),
            bucketSize: 'week',
        );

        $buckets = $horizon->getBuckets();

        $this->assertCount(4, $buckets);
    }

    public function testGetBucketsMonthly(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-04-01'),
            bucketSize: 'month',
        );

        $buckets = $horizon->getBuckets();

        $this->assertCount(3, $buckets);
    }

    public function testGetZoneForDate(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            frozenDays: 14,
            slushyDays: 14,
        );

        // Day 5 should be frozen
        $this->assertSame(PlanningZone::FROZEN, $horizon->getZoneForDate(new \DateTimeImmutable('2024-01-06')));

        // Day 20 should be slushy (14 frozen + 6 days)
        $this->assertSame(PlanningZone::SLUSHY, $horizon->getZoneForDate(new \DateTimeImmutable('2024-01-21')));

        // Day 35 should be liquid
        $this->assertSame(PlanningZone::LIQUID, $horizon->getZoneForDate(new \DateTimeImmutable('2024-02-05')));
    }

    public function testGetFrozenEndDate(): void
    {
        $start = new \DateTimeImmutable('2024-01-01');
        $horizon = new PlanningHorizon(
            startDate: $start,
            endDate: new \DateTimeImmutable('2024-03-31'),
            frozenDays: 14,
        );

        $frozenEnd = $horizon->getFrozenEndDate();
        $this->assertSame('2024-01-15', $frozenEnd->format('Y-m-d'));
    }

    public function testGetSlushyEndDate(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            frozenDays: 14,
            slushyDays: 14,
        );

        $slushyEnd = $horizon->getSlushyEndDate();
        $this->assertSame('2024-01-29', $slushyEnd->format('Y-m-d'));
    }

    public function testGetBucketCount(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            bucketSize: 'day',
        );

        $this->assertSame(30, $horizon->getBucketCount());

        $weeklyHorizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            bucketSize: 'week',
        );

        $this->assertSame(5, $weeklyHorizon->getBucketCount()); // ceil(30/7) = 5
    }

    public function testForDays(): void
    {
        $horizon = PlanningHorizon::forDays(90, 7, 14, 'week');

        $this->assertSame(90, $horizon->getTotalDays());
        $this->assertSame(7, $horizon->frozenDays);
        $this->assertSame(14, $horizon->slushyDays);
        $this->assertSame('week', $horizon->bucketSize);
    }

    public function testForWeeks(): void
    {
        $horizon = PlanningHorizon::forWeeks(12, 2, 2);

        $this->assertSame(84, $horizon->getTotalDays()); // 12 * 7
        $this->assertSame(14, $horizon->frozenDays); // 2 weeks
        $this->assertSame(14, $horizon->slushyDays); // 2 weeks
        $this->assertSame('week', $horizon->bucketSize);
    }

    public function testForMonths(): void
    {
        $horizon = PlanningHorizon::forMonths(3, 2, 2);

        $this->assertSame(90, $horizon->getTotalDays()); // 3 * 30
        $this->assertSame('month', $horizon->bucketSize);
    }

    public function testToArray(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            frozenDays: 14,
            slushyDays: 14,
            liquidDays: 62,
            bucketSize: 'week',
        );

        $array = $horizon->toArray();

        $this->assertSame('2024-01-01', $array['startDate']);
        $this->assertSame('2024-03-31', $array['endDate']);
        $this->assertSame(14, $array['frozenDays']);
        $this->assertSame(14, $array['slushyDays']);
        $this->assertSame(62, $array['liquidDays']);
        $this->assertSame('week', $array['bucketSize']);
        $this->assertSame(90, $array['totalDays']);
    }

    public function testFromArray(): void
    {
        $data = [
            'startDate' => '2024-01-01',
            'endDate' => '2024-03-31',
            'frozenDays' => 14,
            'slushyDays' => 14,
            'liquidDays' => 62,
            'bucketSize' => 'week',
        ];

        $horizon = PlanningHorizon::fromArray($data);

        $this->assertSame('2024-01-01', $horizon->startDate->format('Y-m-d'));
        $this->assertSame('2024-03-31', $horizon->endDate->format('Y-m-d'));
        $this->assertSame(14, $horizon->frozenDays);
    }

    public function testThrowsExceptionForInvalidDateRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End date must be after start date');

        new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-03-31'),
            endDate: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function testThrowsExceptionForInvalidBucketSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bucket size must be day, week, or month');

        new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            bucketSize: 'invalid',
        );
    }
}
