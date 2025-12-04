<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Tests\Unit\Services;

use Nexus\AttendanceManagement\Contracts\AttendanceQueryInterface;
use Nexus\AttendanceManagement\Contracts\AttendancePersistInterface;
use Nexus\AttendanceManagement\Contracts\WorkScheduleQueryInterface;
use Nexus\AttendanceManagement\Entities\AttendanceRecord;
use Nexus\AttendanceManagement\Entities\WorkSchedule;
use Nexus\AttendanceManagement\Enums\AttendanceStatus;
use Nexus\AttendanceManagement\Exceptions\InvalidCheckTimeException;
use Nexus\AttendanceManagement\Services\AttendanceManager;
use Nexus\AttendanceManagement\ValueObjects\AttendanceId;
use Nexus\AttendanceManagement\ValueObjects\ScheduleId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AttendanceManagerTest extends TestCase
{
    private AttendanceManager $manager;
    private MockObject $attendanceQuery;
    private MockObject $attendancePersist;
    private MockObject $scheduleQuery;

    protected function setUp(): void
    {
        $this->attendanceQuery = $this->createMock(AttendanceQueryInterface::class);
        $this->attendancePersist = $this->createMock(AttendancePersistInterface::class);
        $this->scheduleQuery = $this->createMock(WorkScheduleQueryInterface::class);

        $this->manager = new AttendanceManager(
            $this->attendanceQuery,
            $this->attendancePersist,
            $this->scheduleQuery,
            new NullLogger()
        );
    }

    public function test_check_in_creates_new_attendance_record_when_none_exists(): void
    {
        $employeeId = 'emp-123';
        $timestamp = new \DateTimeImmutable('2024-01-15 09:00:00');
        $date = new \DateTimeImmutable('2024-01-15');

        $this->attendanceQuery
            ->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, $date)
            ->willReturn(null);

        $this->scheduleQuery
            ->expects($this->once())
            ->method('findEffectiveSchedule')
            ->willReturn(null);

        $this->attendancePersist
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn($record) => $record);

        $result = $this->manager->checkIn($employeeId, $timestamp, 'office-main', 3.1390, 101.6869);

        $this->assertInstanceOf(AttendanceId::class, $result);
    }

    public function test_check_in_throws_exception_when_already_checked_in(): void
    {
        $employeeId = 'emp-123';
        $timestamp = new \DateTimeImmutable('2024-01-15 09:00:00');
        $date = new \DateTimeImmutable('2024-01-15');
        $checkInTime = new \DateTimeImmutable('2024-01-15 08:00:00');

        $existingRecord = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            $employeeId,
            $date,
            $checkInTime
        );

        $this->attendanceQuery
            ->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->willReturn($existingRecord);

        $this->expectException(InvalidCheckTimeException::class);
        $this->expectExceptionMessage('Employee already checked in for today');

        $this->manager->checkIn($employeeId, $timestamp);
    }

    public function test_check_out_updates_existing_record_with_check_out_time(): void
    {
        $employeeId = 'emp-123';
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOutTime = new \DateTimeImmutable('2024-01-15 17:00:00');
        $date = new \DateTimeImmutable('2024-01-15');

        $existingRecord = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            $employeeId,
            $date,
            $checkInTime
        );

        $this->attendanceQuery
            ->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->willReturn($existingRecord);

        $this->attendancePersist
            ->expects($this->once())
            ->method('save');

        $result = $this->manager->checkOut($employeeId, $checkOutTime);

        $this->assertInstanceOf(AttendanceId::class, $result);
    }

    public function test_check_out_throws_exception_when_not_checked_in(): void
    {
        $employeeId = 'emp-123';
        $timestamp = new \DateTimeImmutable('2024-01-15 17:00:00');
        $date = new \DateTimeImmutable('2024-01-15');

        $this->attendanceQuery
            ->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->willReturn(null);

        $this->expectException(InvalidCheckTimeException::class);
        $this->expectExceptionMessage('Employee has not checked in yet');

        $this->manager->checkOut($employeeId, $timestamp);
    }

    public function test_check_out_throws_exception_when_check_out_before_check_in(): void
    {
        $employeeId = 'emp-123';
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOutTime = new \DateTimeImmutable('2024-01-15 08:00:00'); // Earlier than check-in
        $date = new \DateTimeImmutable('2024-01-15');

        $existingRecord = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            $employeeId,
            $date,
            $checkInTime
        );

        $this->attendanceQuery
            ->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->willReturn($existingRecord);

        $this->expectException(InvalidCheckTimeException::class);
        $this->expectExceptionMessage('Cannot check out before checking in');

        $this->manager->checkOut($employeeId, $checkOutTime);
    }

    public function test_get_attendance_for_date_returns_record(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');

        $expectedRecord = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            $employeeId,
            $date
        );

        $this->attendanceQuery
            ->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, $date)
            ->willReturn($expectedRecord);

        $result = $this->manager->getAttendanceForDate($employeeId, $date);

        $this->assertEquals($expectedRecord, $result);
    }

    public function test_is_checked_in_returns_true_when_employee_has_checked_in(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');

        $this->attendanceQuery
            ->expects($this->once())
            ->method('hasCheckedInToday')
            ->with($employeeId, $date)
            ->willReturn(true);

        $result = $this->manager->isCheckedIn($employeeId, $date);

        $this->assertTrue($result);
    }
}
