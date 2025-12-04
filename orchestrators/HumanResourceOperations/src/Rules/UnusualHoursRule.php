<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\Contracts\AttendanceRuleInterface;
use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Detects unusual working hours (e.g., check-in at 2 AM, working > 16 hours)
 */
final readonly class UnusualHoursRule implements AttendanceRuleInterface
{
    private const int MAX_WORKING_HOURS = 16;
    private const int UNUSUAL_HOUR_START = 1; // 1 AM
    private const int UNUSUAL_HOUR_END = 5;   // 5 AM

    public function check(AttendanceContext $context): RuleCheckResult
    {
        $anomalies = [];
        
        // Check for unusual check-in time (1 AM - 5 AM)
        $hour = (int) $context->timestamp->format('G');
        if ($context->type === 'check_in' && $hour >= self::UNUSUAL_HOUR_START && $hour < self::UNUSUAL_HOUR_END) {
            $anomalies[] = "Unusual check-in time: {$context->timestamp->format('H:i')} (early morning hours)";
        }
        
        // Check for excessive working hours if recent attendance exists
        if ($context->recentAttendance && $context->type === 'check_out') {
            $totalHours = $this->calculateTotalHours($context->recentAttendance, $context->timestamp);
            if ($totalHours > self::MAX_WORKING_HOURS) {
                $anomalies[] = sprintf(
                    'Excessive working hours detected: %.1f hours (threshold: %d hours)',
                    $totalHours,
                    self::MAX_WORKING_HOURS
                );
            }
        }
        
        $passed = empty($anomalies);
        
        return new RuleCheckResult(
            passed: $passed,
            ruleName: $this->getName(),
            message: $passed ? 'No unusual hours detected' : implode('; ', $anomalies),
            metadata: ['anomalies' => $anomalies]
        );
    }

    public function getName(): string
    {
        return 'Unusual Hours Rule';
    }

    private function calculateTotalHours(array $recentAttendance, \DateTimeImmutable $currentTime): float
    {
        // Find matching check-in for this check-out
        foreach ($recentAttendance as $record) {
            if ($record['type'] === 'check_in') {
                $checkIn = new \DateTimeImmutable($record['timestamp']);
                $diff = $currentTime->getTimestamp() - $checkIn->getTimestamp();
                return $diff / 3600; // Convert seconds to hours
            }
        }
        
        return 0.0;
    }
}
