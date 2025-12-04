<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\Common\ValueObjects\Money;
use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Nexus\HumanResourceOperations\Rules\MandatoryComponentsRule;
use PHPUnit\Framework\TestCase;

final class MandatoryComponentsRuleTest extends TestCase
{
    private MandatoryComponentsRule $rule;

    protected function setUp(): void
    {
        $this->rule = new MandatoryComponentsRule();
    }

    public function test_passes_when_all_mandatory_components_present(): void
    {
        $context = new PayrollContext(
            employeeId: 'emp-1',
            periodId: 'period-2024-01',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            baseSalary: Money::of(5000, 'MYR'),
            totalWorkingHours: 168.0,
            totalOvertimeHours: 10.0,
            earnings: [
                'basic_salary' => Money::of(5000, 'MYR'),
                'allowance' => Money::of(500, 'MYR')
            ],
            deductions: [
                'income_tax' => Money::of(500, 'MYR'),
                'employee_provident_fund' => Money::of(550, 'MYR')
            ],
            attendanceRecords: [],
            leaveRecords: []
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('All mandatory components present', $result->message);
    }

    public function test_fails_when_missing_earning_component(): void
    {
        $context = new PayrollContext(
            employeeId: 'emp-1',
            periodId: 'period-2024-01',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            baseSalary: Money::of(5000, 'MYR'),
            totalWorkingHours: 168.0,
            totalOvertimeHours: 10.0,
            earnings: [
                // Missing 'basic_salary'
                'allowance' => Money::of(500, 'MYR')
            ],
            deductions: [
                'income_tax' => Money::of(500, 'MYR'),
                'employee_provident_fund' => Money::of(550, 'MYR')
            ],
            attendanceRecords: [],
            leaveRecords: []
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Missing earning component: basic_salary', $result->message);
    }

    public function test_fails_when_missing_deduction_component(): void
    {
        $context = new PayrollContext(
            employeeId: 'emp-1',
            periodId: 'period-2024-01',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            baseSalary: Money::of(5000, 'MYR'),
            totalWorkingHours: 168.0,
            totalOvertimeHours: 10.0,
            earnings: [
                'basic_salary' => Money::of(5000, 'MYR')
            ],
            deductions: [
                // Missing both 'income_tax' and 'employee_provident_fund'
            ],
            attendanceRecords: [],
            leaveRecords: []
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Missing deduction component', $result->message);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('Mandatory Components Rule', $this->rule->getName());
    }
}
