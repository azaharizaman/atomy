<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Tests\Unit\Entities;

use Nexus\AttendanceManagement\Entities\AttendanceRecord;
use Nexus\AttendanceManagement\Enums\AttendanceStatus;
use Nexus\AttendanceManagement\ValueObjects\AttendanceId;
use Nexus\AttendanceManagement\ValueObjects\WorkHours;
use PHPUnit\Framework\TestCase;

final class AttendanceRecordTest extends TestCase
{
    public function test_creates_attendance_record_with_required_fields(): void
    {
        $id = new AttendanceId('ATT-123');
        $date = new \DateTimeImmutable('2024-01-15');
        
        $record = new AttendanceRecord(
            $id,
            'emp-123',
            $date
        );
        
        $this->assertEquals($id, $record->getId());
        $this->assertEquals('emp-123', $record->getEmployeeId());
        $this->assertEquals($date, $record->getDate());
        $this->assertEquals(AttendanceStatus::ABSENT, $record->getStatus());
        $this->assertFalse($record->isCheckedIn());
        $this->assertFalse($record->isComplete());
    }

    public function test_with_check_in_creates_new_record_with_check_in_time(): void
    {
        $id = new AttendanceId('ATT-123');
        $date = new \DateTimeImmutable('2024-01-15');
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        
        $record = new AttendanceRecord($id, 'emp-123', $date);
        $newRecord = $record->withCheckIn($checkInTime, 'office-main', 3.1390, 101.6869);
        
        $this->assertEquals($checkInTime, $newRecord->getCheckInTime());
        $this->assertEquals('office-main', $newRecord->getLocationId());
        $this->assertEquals(3.1390, $newRecord->getLatitude());
        $this->assertEquals(101.6869, $newRecord->getLongitude());
        $this->assertEquals(AttendanceStatus::PRESENT, $newRecord->getStatus());
        $this->assertTrue($newRecord->isCheckedIn());
        $this->assertFalse($newRecord->isComplete());
    }

    public function test_with_check_out_creates_new_record_with_check_out_time(): void
    {
        $id = new AttendanceId('ATT-123');
        $date = new \DateTimeImmutable('2024-01-15');
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOutTime = new \DateTimeImmutable('2024-01-15 17:00:00');
        $workHours = new WorkHours(8.0, 0.0, 8.0);
        
        $record = new AttendanceRecord($id, 'emp-123', $date, $checkInTime);
        $newRecord = $record->withCheckOut($checkOutTime, $workHours);
        
        $this->assertEquals($checkOutTime, $newRecord->getCheckOutTime());
        $this->assertEquals($workHours, $newRecord->getWorkHours());
        $this->assertFalse($newRecord->isCheckedIn());
        $this->assertTrue($newRecord->isComplete());
    }

    public function test_is_checked_in_returns_true_when_checked_in_without_checkout(): void
    {
        $id = new AttendanceId('ATT-123');
        $date = new \DateTimeImmutable('2024-01-15');
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        
        $record = new AttendanceRecord($id, 'emp-123', $date, $checkInTime);
        
        $this->assertTrue($record->isCheckedIn());
    }

    public function test_is_complete_returns_true_when_both_check_in_and_out_exist(): void
    {
        $id = new AttendanceId('ATT-123');
        $date = new \DateTimeImmutable('2024-01-15');
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOutTime = new \DateTimeImmutable('2024-01-15 17:00:00');
        
        $record = new AttendanceRecord($id, 'emp-123', $date, $checkInTime, $checkOutTime);
        
        $this->assertTrue($record->isComplete());
    }
}
