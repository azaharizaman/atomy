<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\Rules\UnusualHoursRule;
use PHPUnit\Framework\TestCase;

final class UnusualHoursRuleTest extends TestCase
{
    private UnusualHoursRule $rule;

    protected function setUp(): void
    {
        $this->rule = new UnusualHoursRule();
    }

    public function test_passes_for_normal_working_hours(): void
    {
        $context = new AttendanceContext(
            employeeId: 'emp-1',
            timestamp: new \DateTimeImmutable('2024-01-15 09:00:00'),
            type: 'check_in',
            scheduleId: 'sch-1',
            scheduledStart: new \DateTimeImmutable('2024-01-15 09:00:00'),
            scheduledEnd: new \DateTimeImmutable('2024-01-15 18:00:00'),
            locationId: 'loc-1',
            latitude: null,
            longitude: null
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('No unusual hours detected', $result->message);
    }

    public function test_fails_for_early_morning_check_in(): void
    {
        $context = new AttendanceContext(
            employeeId: 'emp-1',
            timestamp: new \DateTimeImmutable('2024-01-15 02:30:00'),
            type: 'check_in',
            scheduleId: 'sch-1',
            scheduledStart: new \DateTimeImmutable('2024-01-15 09:00:00'),
            scheduledEnd: new \DateTimeImmutable('2024-01-15 18:00:00'),
            locationId: 'loc-1',
            latitude: null,
            longitude: null
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Unusual check-in time', $result->message);
        $this->assertStringContainsString('early morning hours', $result->message);
    }

    public function test_fails_for_excessive_working_hours(): void
    {
        $context = new AttendanceContext(
            employeeId: 'emp-1',
            timestamp: new \DateTimeImmutable('2024-01-15 23:30:00'),
            type: 'check_out',
            scheduleId: 'sch-1',
            scheduledStart: new \DateTimeImmutable('2024-01-15 07:00:00'),
            scheduledEnd: new \DateTimeImmutable('2024-01-15 16:00:00'),
            locationId: 'loc-1',
            latitude: null,
            longitude: null,
            recentAttendance: [
                [
                    'type' => 'check_in',
                    'timestamp' => '2024-01-15 07:00:00'
                ]
            ]
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Excessive working hours', $result->message);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('Unusual Hours Rule', $this->rule->getName());
    }
}
