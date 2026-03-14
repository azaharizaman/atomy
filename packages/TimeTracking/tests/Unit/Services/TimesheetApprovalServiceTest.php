<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\TimeTracking\Contracts\TimesheetPersistInterface;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\TimeTracking\Enums\TimesheetStatus;
use Nexus\TimeTracking\Exceptions\TimesheetImmutableException;
use Nexus\TimeTracking\Services\TimesheetApprovalService;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class TimesheetApprovalServiceTest extends TestCase
{
    private TimesheetQueryInterface&MockObject $query;
    private TimesheetPersistInterface&MockObject $persist;
    private TimesheetApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->createMock(TimesheetQueryInterface::class);
        $this->persist = $this->createMock(TimesheetPersistInterface::class);
        $this->service = new TimesheetApprovalService($this->query, $this->persist);
    }

    public function test_approve_persists_approved_status(): void
    {
        $ts = new TimesheetSummary(
            't1',
            'u1',
            'w1',
            new DateTimeImmutable(),
            2.0,
            'Desc',
            TimesheetStatus::Submitted
        );
        $this->query->method('getById')->with('t1')->willReturn($ts);
        $this->persist->expects(self::once())->method('persist')->with(self::callback(
            static function (TimesheetSummary $t) {
                return $t->status === TimesheetStatus::Approved;
            }
        ));
        $this->service->approve('t1');
    }

    public function test_approve_throws_when_already_approved(): void
    {
        $ts = new TimesheetSummary(
            't1',
            'u1',
            'w1',
            new DateTimeImmutable(),
            2.0,
            '',
            TimesheetStatus::Approved
        );
        $this->query->method('getById')->willReturn($ts);
        $this->expectException(TimesheetImmutableException::class);
        $this->service->approve('t1');
    }
}
