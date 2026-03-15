<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\TimeTracking\Contracts\TimesheetPersistInterface;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\TimeTracking\Enums\TimesheetStatus;
use Nexus\TimeTracking\Exceptions\TimesheetImmutableException;
use Nexus\TimeTracking\Services\HoursValidator;
use Nexus\TimeTracking\Services\TimesheetManager;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class TimesheetManagerTest extends TestCase
{
    private TimesheetQueryInterface&MockObject $query;
    private TimesheetPersistInterface&MockObject $persist;
    private TimesheetManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->createMock(TimesheetQueryInterface::class);
        $this->persist = $this->createMock(TimesheetPersistInterface::class);
        $this->manager = new TimesheetManager($this->query, $this->persist, new HoursValidator());
    }

    public function test_submit_draft_persists(): void
    {
        $ts = new TimesheetSummary(
            't1',
            'u1',
            'w1',
            new DateTimeImmutable('2025-01-15'),
            2.0,
            'Desc',
            TimesheetStatus::Draft
        );
        $this->query->method('getTotalHoursByUserAndDate')->willReturn(0.0);
        $this->persist->expects(self::once())->method('persist')->with($ts);
        $this->manager->submit($ts);
    }

    public function test_submit_approved_throws(): void
    {
        $ts = new TimesheetSummary(
            't1',
            'u1',
            'w1',
            new DateTimeImmutable(),
            1.0,
            '',
            TimesheetStatus::Approved
        );
        $this->persist->expects(self::never())->method('persist');
        $this->expectException(TimesheetImmutableException::class);
        $this->manager->submit($ts);
    }
}
