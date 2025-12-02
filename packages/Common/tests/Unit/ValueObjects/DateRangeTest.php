<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\Exceptions\InvalidValueException;
use Nexus\Common\ValueObjects\DateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_of_creates_date_range(): void
    {
        $start = new DateTimeImmutable('2024-01-01');
        $end = new DateTimeImmutable('2024-12-31');
        
        $range = new DateRange($start, $end);
        
        $this->assertEquals($start, $range->getStartDate());
        $this->assertEquals($end, $range->getEndDate());
    }

    public function test_throws_exception_when_end_before_start(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('End date must be after or equal to start date');
        
        $start = new DateTimeImmutable('2024-12-31');
        $end = new DateTimeImmutable('2024-01-01');
        
        new DateRange($start, $end);
    }

    public function test_contains_returns_true_for_date_within_range(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31')
        );
        
        $this->assertTrue($range->contains(new DateTimeImmutable('2024-06-15')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2024-01-01'))); // Start
        $this->assertTrue($range->contains(new DateTimeImmutable('2024-12-31'))); // End
    }

    public function test_contains_returns_false_for_date_outside_range(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31')
        );
        
        $this->assertFalse($range->contains(new DateTimeImmutable('2023-12-31')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-01-01')));
    }

    public function test_overlaps_returns_true_for_overlapping_ranges(): void
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-06-30')
        );
        
        $range2 = new DateRange(
            new DateTimeImmutable('2024-04-01'),
            new DateTimeImmutable('2024-12-31')
        );
        
        $this->assertTrue($range1->overlaps($range2));
        $this->assertTrue($range2->overlaps($range1));
    }

    public function test_overlaps_returns_false_for_non_overlapping_ranges(): void
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-03-31')
        );
        
        $range2 = new DateRange(
            new DateTimeImmutable('2024-04-01'),
            new DateTimeImmutable('2024-12-31')
        );
        
        $this->assertFalse($range1->overlaps($range2));
    }

    public function test_shift_moves_both_dates(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31')
        );
        
        $shifted = $range->shift(months: 1);
        
        $this->assertEquals(new DateTimeImmutable('2024-02-01'), $shifted->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-02-29'), $shifted->getEndDate());
    }

    public function test_shift_with_days(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-10')
        );
        
        $shifted = $range->shift(days: 5);
        
        $this->assertEquals(new DateTimeImmutable('2024-01-06'), $shifted->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-01-15'), $shifted->getEndDate());
    }

    public function test_extend_increases_end_date(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31')
        );
        
        $extended = $range->extend(days: 10);
        
        $this->assertEquals(new DateTimeImmutable('2024-01-01'), $extended->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-02-10'), $extended->getEndDate());
    }

    public function test_get_days_returns_number_of_days(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-10')
        );
        
        $this->assertSame(10, $range->getDays());
    }

    public function test_is_active_returns_true_for_current_date(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $tomorrow = new DateTimeImmutable('tomorrow');
        
        $range = new DateRange($yesterday, $tomorrow);
        
        $this->assertTrue($range->isActive());
    }

    public function test_is_active_returns_false_for_past_range(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-12-31')
        );
        
        $this->assertFalse($range->isActive());
    }

    public function test_compare_to(): void
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-06-30')
        );
        
        $range2 = new DateRange(
            new DateTimeImmutable('2024-07-01'),
            new DateTimeImmutable('2024-12-31')
        );
        
        $this->assertLessThan(0, $range1->compareTo($range2));
        $this->assertGreaterThan(0, $range2->compareTo($range1));
    }

    public function test_to_array(): void
    {
        $start = new DateTimeImmutable('2024-01-01');
        $end = new DateTimeImmutable('2024-12-31');
        $range = new DateRange($start, $end);
        
        $array = $range->toArray();
        
        $this->assertArrayHasKey('start_date', $array);
        $this->assertArrayHasKey('end_date', $array);
        $this->assertSame('2024-01-01T00:00:00+00:00', $array['start_date']);
    }

    public function test_from_array(): void
    {
        $data = [
            'start_date' => '2024-01-01T00:00:00+00:00',
            'end_date' => '2024-12-31T23:59:59+00:00',
        ];
        
        $range = DateRange::fromArray($data);
        
        $this->assertEquals(new DateTimeImmutable('2024-01-01'), $range->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-12-31 23:59:59'), $range->getEndDate());
    }
}
