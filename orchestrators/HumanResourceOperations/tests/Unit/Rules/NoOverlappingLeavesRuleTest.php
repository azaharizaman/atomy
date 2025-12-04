<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\Rules\NoOverlappingLeavesRule;
use PHPUnit\Framework\TestCase;

final class NoOverlappingLeavesRuleTest extends TestCase
{
    private NoOverlappingLeavesRule $rule;

    protected function setUp(): void
    {
        $this->rule = new NoOverlappingLeavesRule();
    }

    public function test_passes_when_no_overlaps(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-02-01'),
            endDate: new \DateTimeImmutable('2024-02-03'),
            daysRequested: 3.0,
            currentBalance: 10.0,
            policyRules: [],
            existingLeaves: [
                [
                    'id' => 'leave-1',
                    'start_date' => '2024-01-15',
                    'end_date' => '2024-01-17',
                    'status' => 'approved'
                ],
                [
                    'id' => 'leave-2',
                    'start_date' => '2024-03-01',
                    'end_date' => '2024-03-05',
                    'status' => 'approved'
                ]
            ]
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('No overlapping leaves', $result->message);
    }

    public function test_fails_when_overlap_exists(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-01-16'),
            endDate: new \DateTimeImmutable('2024-01-18'),
            daysRequested: 3.0,
            currentBalance: 10.0,
            policyRules: [],
            existingLeaves: [
                [
                    'id' => 'leave-1',
                    'start_date' => '2024-01-15',
                    'end_date' => '2024-01-17',
                    'status' => 'approved'
                ]
            ]
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('overlaps with existing leave', $result->message);
        $this->assertArrayHasKey('overlapping_leaves', $result->metadata);
        $this->assertCount(1, $result->metadata['overlapping_leaves']);
    }

    public function test_passes_when_existing_leaves_empty(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-01-15'),
            endDate: new \DateTimeImmutable('2024-01-17'),
            daysRequested: 3.0,
            currentBalance: 10.0,
            policyRules: [],
            existingLeaves: []
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
    }

    public function test_ignores_cancelled_leaves(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-01-15'),
            endDate: new \DateTimeImmutable('2024-01-17'),
            daysRequested: 3.0,
            currentBalance: 10.0,
            policyRules: [],
            existingLeaves: [
                [
                    'id' => 'leave-1',
                    'start_date' => '2024-01-15',
                    'end_date' => '2024-01-17',
                    'status' => 'cancelled'
                ]
            ]
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('No Overlapping Leaves Rule', $this->rule->getName());
    }
}
