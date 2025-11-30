<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Unit\Core\Engine;

use DateTimeImmutable;
use Nexus\Scheduler\Core\Engine\RecurrenceEngine;
use Nexus\Scheduler\Enums\RecurrenceType;
use Nexus\Scheduler\Exceptions\InvalidRecurrenceException;
use Nexus\Scheduler\Tests\Support\MutableClock;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;
use PHPUnit\Framework\TestCase;

final class RecurrenceEngineTest extends TestCase
{
    private MutableClock $clock;
    private RecurrenceEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new MutableClock(new DateTimeImmutable('2024-01-01 00:00:00'));
        $this->engine = new RecurrenceEngine($this->clock);
    }

    public function test_calculate_next_run_daily(): void
    {
        $recurrence = ScheduleRecurrence::daily();
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 1);
        
        self::assertNotNull($nextRun);
        self::assertSame('2024-01-02 10:00:00', $nextRun->format('Y-m-d H:i:s'));
    }

    public function test_calculate_next_run_weekly(): void
    {
        $recurrence = ScheduleRecurrence::weekly();
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 1);
        
        self::assertNotNull($nextRun);
        self::assertSame('2024-01-08 10:00:00', $nextRun->format('Y-m-d H:i:s'));
    }

    public function test_calculate_next_run_monthly(): void
    {
        $recurrence = ScheduleRecurrence::monthly();
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 1);
        
        self::assertNotNull($nextRun);
        self::assertSame('2024-02-01 10:00:00', $nextRun->format('Y-m-d H:i:s'));
    }

    public function test_calculate_next_run_returns_null_when_ended_by_date(): void
    {
        $recurrence = new ScheduleRecurrence(
            type: RecurrenceType::DAILY,
            endsAt: new DateTimeImmutable('2024-01-01 23:59:59')
        );
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        
        // Advance clock past end date
        $this->clock->set(new DateTimeImmutable('2024-01-02 00:00:00'));
        
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 1);
        
        self::assertNull($nextRun);
    }

    public function test_calculate_next_run_returns_null_when_max_occurrences_reached(): void
    {
        $recurrence = new ScheduleRecurrence(
            type: RecurrenceType::DAILY,
            maxOccurrences: 5
        );
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 5);
        
        self::assertNull($nextRun);
    }

    public function test_calculate_next_run_cron(): void
    {
        $recurrence = ScheduleRecurrence::cron('0 10 * * *'); // Every day at 10:00
        $currentRunAt = new DateTimeImmutable('2024-01-01 09:00:00');
        
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 1);
        
        self::assertNotNull($nextRun);
        self::assertSame('2024-01-01 10:00:00', $nextRun->format('Y-m-d H:i:s'));

        // Test next day
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $nextRun = $this->engine->calculateNextRunTime($currentRunAt, $recurrence, 1);
        
        self::assertNotNull($nextRun);
        self::assertSame('2024-01-02 10:00:00', $nextRun->format('Y-m-d H:i:s'));
    }

    public function test_describe_next_run(): void
    {
        $recurrence = ScheduleRecurrence::daily();
        $currentRunAt = new DateTimeImmutable('2024-01-01 10:00:00');
        
        // Next run will be 2024-01-02 10:00:00
        // Current time is 2024-01-01 00:00:00
        // Diff is 1 day 10 hours
        
        $description = $this->engine->describeNextRun($currentRunAt, $recurrence);
        
        self::assertSame('In 1 days', $description);
    }
}
