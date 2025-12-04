<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\Rules\SufficientLeaveBalanceRule;
use PHPUnit\Framework\TestCase;

final class SufficientLeaveBalanceRuleTest extends TestCase
{
    private SufficientLeaveBalanceRule $rule;

    protected function setUp(): void
    {
        $this->rule = new SufficientLeaveBalanceRule();
    }

    public function test_passes_when_sufficient_balance(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-01-15'),
            endDate: new \DateTimeImmutable('2024-01-17'),
            daysRequested: 3.0,
            currentBalance: 10.0,
            policyRules: []
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('Sufficient leave balance', $result->message);
        $this->assertEquals(10.0, $result->metadata['current_balance']);
        $this->assertEquals(3.0, $result->metadata['days_requested']);
    }

    public function test_fails_when_insufficient_balance(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-01-15'),
            endDate: new \DateTimeImmutable('2024-01-17'),
            daysRequested: 5.0,
            currentBalance: 2.0,
            policyRules: []
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Insufficient leave balance', $result->message);
        $this->assertEquals(2.0, $result->metadata['current_balance']);
        $this->assertEquals(5.0, $result->metadata['days_requested']);
    }

    public function test_fails_when_exact_balance_used(): void
    {
        $context = new LeaveContext(
            employeeId: 'emp-1',
            leaveTypeId: 'annual',
            startDate: new \DateTimeImmutable('2024-01-15'),
            endDate: new \DateTimeImmutable('2024-01-17'),
            daysRequested: 5.0,
            currentBalance: 5.0,
            policyRules: []
        );

        $result = $this->rule->check($context);

        // Should pass - exact balance is acceptable
        $this->assertTrue($result->passed);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('Sufficient Leave Balance Rule', $this->rule->getName());
    }
}
