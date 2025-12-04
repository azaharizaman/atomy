<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Integration\Coordinators;

use Nexus\HumanResourceOperations\Coordinators\AttendanceCoordinator;
use Nexus\HumanResourceOperations\DataProviders\AttendanceDataProvider;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckRequest;
use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\Rules\UnusualHoursRule;
use Nexus\HumanResourceOperations\Rules\LocationAnomalyRule;
use Nexus\HumanResourceOperations\Services\AttendanceRuleRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AttendanceCoordinatorTest extends TestCase
{
    private AttendanceCoordinator $coordinator;
    private MockObject $dataProvider;

    protected function setUp(): void
    {
        // Mock DataProvider
        $this->dataProvider = $this->createMock(AttendanceDataProvider::class);
        
        // Create real RuleRegistry with real rules
        $ruleRegistry = new AttendanceRuleRegistry([
            new UnusualHoursRule(),
            new LocationAnomalyRule(
                officeLatitude: 3.1390,
                officeLongitude: 101.6869
            )
        ]);

        $this->coordinator = new AttendanceCoordinator(
            dataProvider: $this->dataProvider,
            ruleRegistry: $ruleRegistry,
            logger: new NullLogger()
        );
    }

    public function test_successfully_processes_normal_check_in(): void
    {
        // Arrange
        $request = new AttendanceCheckRequest(
            employeeId: 'emp-123',
            timestamp: new \DateTimeImmutable('2024-01-15 09:00:00'),
            type: 'check_in',
            locationId: 'office-main',
            latitude: 3.1390,
            longitude: 101.6869
        );

        $context = new AttendanceContext(
            employeeId: 'emp-123',
            timestamp: new \DateTimeImmutable('2024-01-15 09:00:00'),
            type: 'check_in',
            scheduleId: 'sch-123',
            scheduledStart: new \DateTimeImmutable('2024-01-15 09:00:00'),
            scheduledEnd: new \DateTimeImmutable('2024-01-15 18:00:00'),
            locationId: 'office-main',
            latitude: 3.1390,
            longitude: 101.6869,
            recentAttendance: []
        );

        $this->dataProvider
            ->expects($this->once())
            ->method('getAttendanceContext')
            ->willReturn($context);

        // Act
        $result = $this->coordinator->processAttendanceCheck($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->attendanceId);
        $this->assertFalse($result->hasAnomalies());
    }

    public function test_detects_unusual_check_in_time(): void
    {
        // Arrange
        $request = new AttendanceCheckRequest(
            employeeId: 'emp-123',
            timestamp: new \DateTimeImmutable('2024-01-15 02:30:00'),
            type: 'check_in',
            locationId: 'office-main',
            latitude: 3.1390,
            longitude: 101.6869
        );

        $context = new AttendanceContext(
            employeeId: 'emp-123',
            timestamp: new \DateTimeImmutable('2024-01-15 02:30:00'),
            type: 'check_in',
            scheduleId: 'sch-123',
            scheduledStart: new \DateTimeImmutable('2024-01-15 09:00:00'),
            scheduledEnd: new \DateTimeImmutable('2024-01-15 18:00:00'),
            locationId: 'office-main',
            latitude: 3.1390,
            longitude: 101.6869,
            recentAttendance: []
        );

        $this->dataProvider
            ->expects($this->once())
            ->method('getAttendanceContext')
            ->willReturn($context);

        // Act
        $result = $this->coordinator->processAttendanceCheck($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertTrue($result->hasAnomalies());
        $this->assertNotEmpty($result->anomalies);
        $this->assertStringContainsString('anomaly', strtolower($result->message));
    }

    public function test_detects_location_anomaly(): void
    {
        // Arrange - Check-in from 100km away
        $request = new AttendanceCheckRequest(
            employeeId: 'emp-123',
            timestamp: new \DateTimeImmutable('2024-01-15 09:00:00'),
            type: 'check_in',
            locationId: null,
            latitude: 4.2105, // Penang coordinates
            longitude: 101.9758
        );

        $context = new AttendanceContext(
            employeeId: 'emp-123',
            timestamp: new \DateTimeImmutable('2024-01-15 09:00:00'),
            type: 'check_in',
            scheduleId: 'sch-123',
            scheduledStart: new \DateTimeImmutable('2024-01-15 09:00:00'),
            scheduledEnd: new \DateTimeImmutable('2024-01-15 18:00:00'),
            locationId: null,
            latitude: 4.2105,
            longitude: 101.9758,
            recentAttendance: []
        );

        $this->dataProvider
            ->expects($this->once())
            ->method('getAttendanceContext')
            ->willReturn($context);

        // Act
        $result = $this->coordinator->processAttendanceCheck($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertTrue($result->hasAnomalies());
    }
}
