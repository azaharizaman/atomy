<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\TimeTracking\Enums\TimesheetStatus;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;
use PHPUnit\Framework\TestCase;

final class TimesheetSummaryTest extends TestCase
{
    public function test_is_approved_when_status_approved(): void
    {
        $ts = new TimesheetSummary(
            '1',
            'u1',
            'w1',
            new DateTimeImmutable('2025-01-15'),
            2.5,
            'Work',
            TimesheetStatus::Approved
        );
        self::assertTrue($ts->isApproved());
    }

    public function test_throws_on_negative_hours(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('0 and 24');
        new TimesheetSummary(
            '1',
            'u1',
            'w1',
            new DateTimeImmutable(),
            -1.0,
            '',
            TimesheetStatus::Draft
        );
    }

    public function test_throws_on_over_24_hours(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TimesheetSummary(
            '1',
            'u1',
            'w1',
            new DateTimeImmutable(),
            25.0,
            '',
            TimesheetStatus::Draft
        );
    }

    public function test_throws_on_empty_work_item_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Work item id');
        new TimesheetSummary(
            '1',
            'u1',
            '',
            new DateTimeImmutable(),
            1.0,
            '',
            TimesheetStatus::Draft
        );
    }
}
