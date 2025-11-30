<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Core\Engine;

use DateTimeImmutable;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Enums\RecurrenceType;
use Nexus\Scheduler\Exceptions\InvalidRecurrenceException;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;

/**
 * Recurrence Engine
 *
 * Calculates next occurrence times for recurring schedules.
 * Handles both simple intervals and cron expressions.
 */
final readonly class RecurrenceEngine
{
    public function __construct(
        private ClockInterface $clock
    ) {}
    
    /**
     * Calculate the next run time for a recurring schedule
     *
     * @param DateTimeImmutable $currentRunAt Current run time
     * @param ScheduleRecurrence $recurrence Recurrence configuration
     * @param int $occurrenceCount Current occurrence count
     * @return DateTimeImmutable|null Next run time or null if recurrence has ended
     */
    public function calculateNextRunTime(
        DateTimeImmutable $currentRunAt,
        ScheduleRecurrence $recurrence,
        int $occurrenceCount
    ): ?DateTimeImmutable {
        // Check if recurrence has ended
        if ($recurrence->hasEnded($this->clock->now(), $occurrenceCount)) {
            return null;
        }
        
        // Handle cron expressions
        if ($recurrence->type === RecurrenceType::CRON) {
            return $this->calculateCronNextRun($currentRunAt, $recurrence->cronExpression);
        }
        
        // Handle simple intervals
        return $this->calculateIntervalNextRun($currentRunAt, $recurrence);
    }
    
    /**
     * Calculate next run time for interval-based recurrence
     */
    private function calculateIntervalNextRun(
        DateTimeImmutable $currentRunAt,
        ScheduleRecurrence $recurrence
    ): DateTimeImmutable {
        $intervalSeconds = $recurrence->getIntervalSeconds();
        
        if ($intervalSeconds === null) {
            throw new InvalidRecurrenceException(
                "Cannot calculate interval for recurrence type: {$recurrence->type->value}"
            );
        }
        
        return match($recurrence->type) {
            RecurrenceType::MINUTELY => $currentRunAt->modify("+{$recurrence->interval} minutes"),
            RecurrenceType::HOURLY => $currentRunAt->modify("+{$recurrence->interval} hours"),
            RecurrenceType::DAILY => $currentRunAt->modify("+{$recurrence->interval} days"),
            RecurrenceType::WEEKLY => $currentRunAt->modify("+{$recurrence->interval} weeks"),
            RecurrenceType::MONTHLY => $currentRunAt->modify("+{$recurrence->interval} months"),
            RecurrenceType::YEARLY => $currentRunAt->modify("+{$recurrence->interval} years"),
            default => throw new InvalidRecurrenceException(
                "Unsupported recurrence type: {$recurrence->type->value}"
            ),
        };
    }
    
    /**
     * Calculate next run time for cron expression
     *
     * Requires dragonmantank/cron-expression package.
     */
    private function calculateCronNextRun(
        DateTimeImmutable $currentRunAt,
        ?string $cronExpression
    ): DateTimeImmutable {
        if ($cronExpression === null) {
            throw new InvalidRecurrenceException('Cron expression is required for CRON recurrence type');
        }
        
        // Check if cron-expression library is available
        if (!class_exists(\Cron\CronExpression::class)) {
            throw new InvalidRecurrenceException(
                'Cron expression support requires dragonmantank/cron-expression package. ' .
                'Install it with: composer require dragonmantank/cron-expression'
            );
        }
        
        try {
            $cron = new \Cron\CronExpression($cronExpression);
            $nextRun = $cron->getNextRunDate($currentRunAt);
            
            return DateTimeImmutable::createFromMutable($nextRun);
        } catch (\Throwable $e) {
            throw new InvalidRecurrenceException(
                "Invalid cron expression '{$cronExpression}': {$e->getMessage()}"
            );
        }
    }
    
    /**
     * Validate a cron expression
     *
     * @param string $cronExpression Cron expression to validate
     * @return bool True if valid
     * @throws InvalidRecurrenceException If expression is invalid
     */
    public function validateCronExpression(string $cronExpression): bool
    {
        if (!class_exists(\Cron\CronExpression::class)) {
            throw new InvalidRecurrenceException(
                'Cron expression validation requires dragonmantank/cron-expression package'
            );
        }
        
        try {
            new \Cron\CronExpression($cronExpression);
            return true;
        } catch (\Throwable $e) {
            throw new InvalidRecurrenceException(
                "Invalid cron expression '{$cronExpression}': {$e->getMessage()}"
            );
        }
    }
    
    /**
     * Get human-readable description of next run time
     */
    public function describeNextRun(
        DateTimeImmutable $currentRunAt,
        ScheduleRecurrence $recurrence
    ): string {
        $nextRun = $this->calculateNextRunTime($currentRunAt, $recurrence, 0);
        
        if ($nextRun === null) {
            return 'No more occurrences';
        }
        
        $interval = $this->clock->now()->diff($nextRun);
        
        if ($interval->days > 0) {
            return "In {$interval->days} days";
        }
        
        if ($interval->h > 0) {
            return "In {$interval->h} hours";
        }
        
        if ($interval->i > 0) {
            return "In {$interval->i} minutes";
        }
        
        return 'In less than a minute';
    }
}
