<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\Contracts\PayrollRuleInterface;
use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Validates overtime hours don't exceed legal or company limits
 */
final readonly class ExceedsMaxOvertimeRule implements PayrollRuleInterface
{
    private const float MAX_MONTHLY_OVERTIME_HOURS = 104.0; // Malaysian Employment Act limit
    private const float MAX_DAILY_OVERTIME_HOURS = 4.0;

    public function check(PayrollContext $context): RuleCheckResult
    {
        $violations = [];
        
        // Check total monthly overtime
        if ($context->totalOvertimeHours > self::MAX_MONTHLY_OVERTIME_HOURS) {
            $violations[] = sprintf(
                'Overtime hours (%.1f) exceed monthly limit (%.1f hours)',
                $context->totalOvertimeHours,
                self::MAX_MONTHLY_OVERTIME_HOURS
            );
        }
        
        // Check daily overtime from attendance records
        $dailyOvertimeViolations = $this->checkDailyOvertimeViolations($context->attendanceRecords);
        if (!empty($dailyOvertimeViolations)) {
            $violations = array_merge($violations, $dailyOvertimeViolations);
        }
        
        $passed = empty($violations);
        
        return new RuleCheckResult(
            passed: $passed,
            ruleName: $this->getName(),
            message: $passed 
                ? 'Overtime hours within legal limits'
                : implode('; ', $violations),
            metadata: [
                'total_overtime_hours' => $context->totalOvertimeHours,
                'max_monthly_overtime' => self::MAX_MONTHLY_OVERTIME_HOURS,
                'violations' => $violations
            ]
        );
    }

    public function getName(): string
    {
        return 'Exceeds Max Overtime Rule';
    }

    private function checkDailyOvertimeViolations(array $attendanceRecords): array
    {
        $violations = [];
        
        foreach ($attendanceRecords as $record) {
            $overtimeHours = $record['overtime_hours'] ?? 0.0;
            if ($overtimeHours > self::MAX_DAILY_OVERTIME_HOURS) {
                $date = $record['date'] ?? 'unknown';
                $violations[] = sprintf(
                    'Daily overtime on %s (%.1f hours) exceeds limit (%.1f hours)',
                    $date,
                    $overtimeHours,
                    self::MAX_DAILY_OVERTIME_HOURS
                );
            }
        }
        
        return $violations;
    }
}
