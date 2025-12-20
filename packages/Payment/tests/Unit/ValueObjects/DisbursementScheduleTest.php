<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Payment\Enums\RecurrenceFrequency;
use Nexus\Payment\Enums\ScheduleType;
use Nexus\Payment\Exceptions\InvalidScheduleException;
use Nexus\Payment\ValueObjects\DisbursementSchedule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisbursementSchedule::class)]
final class DisbursementScheduleTest extends TestCase
{
    #[Test]
    public function it_creates_immediate_schedule(): void
    {
        $schedule = DisbursementSchedule::immediate();

        $this->assertSame(ScheduleType::IMMEDIATE, $schedule->scheduleType);
        $this->assertNull($schedule->scheduledDate);
        $this->assertNull($schedule->recurrenceFrequency);
        $this->assertNull($schedule->recurrenceEndDate);
        $this->assertNull($schedule->maxOccurrences);
        $this->assertTrue($schedule->isReadyForProcessing());
    }

    #[Test]
    public function it_creates_scheduled_disbursement(): void
    {
        $scheduledDate = new \DateTimeImmutable('+7 days');
        $schedule = DisbursementSchedule::scheduled($scheduledDate);

        $this->assertSame(ScheduleType::SCHEDULED, $schedule->scheduleType);
        $this->assertEquals($scheduledDate, $schedule->scheduledDate);
        $this->assertNull($schedule->recurrenceFrequency);
        $this->assertFalse($schedule->isReadyForProcessing());
    }

    #[Test]
    public function it_throws_for_past_scheduled_date(): void
    {
        $this->expectException(InvalidScheduleException::class);

        $pastDate = new \DateTimeImmutable('-1 day');
        DisbursementSchedule::scheduled($pastDate);
    }

    #[Test]
    public function it_creates_recurring_schedule(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');
        $endDate = new \DateTimeImmutable('+1 year');

        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::MONTHLY,
            endDate: $endDate,
            maxOccurrences: 12,
        );

        $this->assertSame(ScheduleType::RECURRING, $schedule->scheduleType);
        $this->assertEquals($startDate, $schedule->scheduledDate);
        $this->assertSame(RecurrenceFrequency::MONTHLY, $schedule->recurrenceFrequency);
        $this->assertEquals($endDate, $schedule->recurrenceEndDate);
        $this->assertSame(12, $schedule->maxOccurrences);
        $this->assertSame(0, $schedule->currentOccurrence);
    }

    #[Test]
    public function it_checks_if_ready_for_processing_for_immediate(): void
    {
        $schedule = DisbursementSchedule::immediate();
        $this->assertTrue($schedule->isReadyForProcessing());
    }

    #[Test]
    public function it_checks_if_ready_for_processing_for_future_schedule(): void
    {
        $futureDate = new \DateTimeImmutable('+7 days');
        $schedule = DisbursementSchedule::scheduled($futureDate);

        // Not ready yet since scheduled in future
        $this->assertFalse($schedule->isReadyForProcessing());

        // Ready when reference time is after scheduled date
        $referenceTime = new \DateTimeImmutable('+8 days');
        $this->assertTrue($schedule->isReadyForProcessing($referenceTime));
    }

    #[Test]
    public function it_checks_if_has_more_occurrences(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');

        // With max occurrences
        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::MONTHLY,
            maxOccurrences: 3,
        );

        $this->assertTrue($schedule->hasMoreOccurrences());

        // Simulate 3 occurrences
        $schedule = $schedule->incrementOccurrence()
            ->incrementOccurrence()
            ->incrementOccurrence();

        $this->assertFalse($schedule->hasMoreOccurrences());
    }

    #[Test]
    public function it_calculates_next_occurrence_for_first_run(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');

        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::MONTHLY,
        );

        // First occurrence should be the start date itself
        $next = $schedule->calculateNextOccurrence();
        $this->assertEquals($startDate->format('Y-m-d'), $next->format('Y-m-d'));
    }

    #[Test]
    public function it_calculates_next_occurrence_after_increment(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');

        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::MONTHLY,
        );

        // After one occurrence
        $schedule = $schedule->incrementOccurrence();
        $next = $schedule->calculateNextOccurrence();

        $expectedNext = $startDate->modify('+1 month');
        $this->assertEquals($expectedNext->format('Y-m-d'), $next->format('Y-m-d'));
    }

    #[Test]
    public function it_increments_occurrence_counter(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');

        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::WEEKLY,
        );

        $this->assertSame(0, $schedule->currentOccurrence);

        $schedule = $schedule->incrementOccurrence();
        $this->assertSame(1, $schedule->currentOccurrence);

        $schedule = $schedule->incrementOccurrence();
        $this->assertSame(2, $schedule->currentOccurrence);
    }

    #[Test]
    public function it_serializes_to_array(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');
        $endDate = new \DateTimeImmutable('+6 months');

        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::BIWEEKLY,
            endDate: $endDate,
            maxOccurrences: 10,
        );

        $array = $schedule->toArray();

        $this->assertSame('recurring', $array['schedule_type']);
        $this->assertNotNull($array['scheduled_date']);
        $this->assertSame('biweekly', $array['recurrence_frequency']);
        $this->assertNotNull($array['recurrence_end_date']);
        $this->assertSame(10, $array['max_occurrences']);
        $this->assertSame(0, $array['current_occurrence']);
    }

    #[Test]
    public function it_deserializes_from_array(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');
        $endDate = new \DateTimeImmutable('+6 months');

        $data = [
            'schedule_type' => 'recurring',
            'scheduled_date' => $startDate->format(\DateTimeInterface::ATOM),
            'recurrence_frequency' => 'biweekly',
            'recurrence_end_date' => $endDate->format(\DateTimeInterface::ATOM),
            'max_occurrences' => 10,
            'current_occurrence' => 3,
        ];

        $schedule = DisbursementSchedule::fromArray($data);

        $this->assertSame(ScheduleType::RECURRING, $schedule->scheduleType);
        $this->assertEquals($startDate->format('Y-m-d'), $schedule->scheduledDate->format('Y-m-d'));
        $this->assertSame(RecurrenceFrequency::BIWEEKLY, $schedule->recurrenceFrequency);
        $this->assertEquals($endDate->format('Y-m-d'), $schedule->recurrenceEndDate->format('Y-m-d'));
        $this->assertSame(10, $schedule->maxOccurrences);
        $this->assertSame(3, $schedule->currentOccurrence);
    }

    #[Test]
    public function it_handles_different_frequencies(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');

        $frequencies = [
            RecurrenceFrequency::DAILY,
            RecurrenceFrequency::WEEKLY,
            RecurrenceFrequency::BIWEEKLY,
            RecurrenceFrequency::MONTHLY,
            RecurrenceFrequency::QUARTERLY,
            RecurrenceFrequency::ANNUALLY,
        ];

        foreach ($frequencies as $frequency) {
            $schedule = DisbursementSchedule::recurring(
                startDate: $startDate,
                frequency: $frequency,
            );

            // First occurrence should be start date
            $next = $schedule->calculateNextOccurrence();
            $this->assertEquals(
                $startDate->format('Y-m-d'),
                $next->format('Y-m-d'),
                "Failed for frequency: {$frequency->value}"
            );
        }
    }

    #[Test]
    public function it_checks_is_recurring(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');
        
        $recurring = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: RecurrenceFrequency::MONTHLY,
        );
        
        $immediate = DisbursementSchedule::immediate();
        
        $this->assertTrue($recurring->isRecurring());
        $this->assertFalse($immediate->isRecurring());
    }

    #[Test]
    public function it_checks_is_immediate(): void
    {
        $startDate = new \DateTimeImmutable('+1 day');
        
        $immediate = DisbursementSchedule::immediate();
        $scheduled = DisbursementSchedule::scheduled($startDate);
        
        $this->assertTrue($immediate->isImmediate());
        $this->assertFalse($scheduled->isImmediate());
    }
}
