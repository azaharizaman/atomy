<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\Contracts\AttendanceRuleInterface;
use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Detects consecutive absences that may indicate a pattern or issue
 */
final readonly class ConsecutiveAbsencesRule implements AttendanceRuleInterface
{
    private const int THRESHOLD_DAYS = 3;

    public function check(AttendanceContext $context): RuleCheckResult
    {
        $consecutiveAbsences = 0;
        
        if ($context->recentAttendance) {
            $consecutiveAbsences = $this->countConsecutiveAbsences($context->recentAttendance);
        }
        
        $passed = $consecutiveAbsences < self::THRESHOLD_DAYS;
        
        $message = $passed
            ? 'No concerning absence pattern detected'
            : sprintf(
                'Warning: %d consecutive absences detected (threshold: %d days)',
                $consecutiveAbsences,
                self::THRESHOLD_DAYS
            );
        
        return new RuleCheckResult(
            passed: $passed,
            ruleName: $this->getName(),
            message: $message,
            metadata: ['consecutive_absences' => $consecutiveAbsences]
        );
    }

    public function getName(): string
    {
        return 'Consecutive Absences Rule';
    }

    private function countConsecutiveAbsences(array $recentAttendance): int
    {
        $count = 0;
        $currentStreak = 0;
        
        // Sort by date descending (most recent first)
        usort($recentAttendance, fn($a, $b) => 
            strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '')
        );
        
        $previousDate = null;
        
        foreach ($recentAttendance as $record) {
            $date = new \DateTimeImmutable($record['date'] ?? 'now');
            
            if ($record['is_absent'] ?? false) {
                if ($previousDate === null) {
                    $currentStreak = 1;
                } else {
                    $diff = $previousDate->diff($date)->days;
                    if ($diff === 1) {
                        $currentStreak++;
                    } else {
                        break; // Streak broken
                    }
                }
                $count = max($count, $currentStreak);
            } else {
                break; // Present day breaks the streak
            }
            
            $previousDate = $date;
        }
        
        return $count;
    }
}
