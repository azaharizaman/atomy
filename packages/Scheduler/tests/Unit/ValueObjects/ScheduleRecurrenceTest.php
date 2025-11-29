<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Scheduler\Enums\RecurrenceType;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;
use PHPUnit\Framework\TestCase;

final class ScheduleRecurrenceTest extends TestCase
{
    public function test_create_once(): void
    {
        $recurrence = ScheduleRecurrence::once();
        
        self::assertSame(RecurrenceType::ONCE, $recurrence->type);
        self::assertFalse($recurrence->isRepeating());
        self::assertSame('One time', $recurrence->describe());
    }

    public function test_create_daily(): void
    {
        $recurrence = ScheduleRecurrence::daily(2);
        
        self::assertSame(RecurrenceType::DAILY, $recurrence->type);
        self::assertSame(2, $recurrence->interval);
        self::assertTrue($recurrence->isRepeating());
        self::assertSame('Every 2 daily', $recurrence->describe());
    }

    public function test_create_weekly(): void
    {
        $recurrence = ScheduleRecurrence::weekly();
        
        self::assertSame(RecurrenceType::WEEKLY, $recurrence->type);
        self::assertSame(1, $recurrence->interval);
        self::assertTrue($recurrence->isRepeating());
        self::assertSame('Weekly', $recurrence->describe());
    }

    public function test_create_monthly(): void
    {
        $recurrence = ScheduleRecurrence::monthly();
        
        self::assertSame(RecurrenceType::MONTHLY, $recurrence->type);
        self::assertSame(1, $recurrence->interval);
        self::assertTrue($recurrence->isRepeating());
        self::assertSame('Monthly', $recurrence->describe());
    }

    public function test_create_cron(): void
    {
        $expression = '0 0 * * *';
        $recurrence = ScheduleRecurrence::cron($expression);
        
        self::assertSame(RecurrenceType::CRON, $recurrence->type);
        self::assertSame($expression, $recurrence->cronExpression);
        self::assertTrue($recurrence->isRepeating());
        self::assertSame("Cron: {$expression}", $recurrence->describe());
    }

    public function test_invalid_interval_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recurrence interval must be at least 1');
        
        new ScheduleRecurrence(RecurrenceType::DAILY, 0);
    }

    public function test_cron_type_requires_expression(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cron expression is required for CRON recurrence type');
        
        new ScheduleRecurrence(RecurrenceType::CRON);
    }

    public function test_non_cron_type_cannot_have_expression(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cron expression is only valid for CRON recurrence type');
        
        new ScheduleRecurrence(RecurrenceType::DAILY, 1, '* * * * *');
    }

    public function test_has_ended_with_end_date(): void
    {
        $endsAt = new DateTimeImmutable('2024-01-01');
        $recurrence = new ScheduleRecurrence(
            type: RecurrenceType::DAILY,
            endsAt: $endsAt
        );
        
        self::assertFalse($recurrence->hasEnded(new DateTimeImmutable('2023-12-31'), 0));
        self::assertTrue($recurrence->hasEnded(new DateTimeImmutable('2024-01-02'), 0));
    }

    public function test_has_ended_with_max_occurrences(): void
    {
        $recurrence = new ScheduleRecurrence(
            type: RecurrenceType::DAILY,
            maxOccurrences: 5
        );
        
        self::assertFalse($recurrence->hasEnded(new DateTimeImmutable(), 4));
        self::assertTrue($recurrence->hasEnded(new DateTimeImmutable(), 5));
    }

    public function test_serialization(): void
    {
        $recurrence = ScheduleRecurrence::daily(2);
        $array = $recurrence->toArray();
        
        self::assertSame('daily', $array['type']);
        self::assertSame(2, $array['interval']);
        
        $restored = ScheduleRecurrence::fromArray($array);
        
        self::assertSame($recurrence->type, $restored->type);
        self::assertSame($recurrence->interval, $restored->interval);
    }
}
