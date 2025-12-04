<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

/**
 * Service for working days calculation.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Services perform calculations, not Coordinators
 * - Stateless business logic
 */
final readonly class WorkingDaysCalculationService
{
    /**
     * Calculate working days between two dates.
     * 
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @return float Number of working days (inclusive)
     */
    public function calculate(string $startDate, string $endDate): float
    {
        // Simplified calculation - should use business calendar
        // In production, this would integrate with Nexus\Calendar or similar
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
        $diff = $start->diff($end);
        
        return $diff->days + 1;
    }

    /**
     * Calculate working days excluding weekends.
     * 
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @return float Number of working days (Monday-Friday)
     */
    public function calculateExcludingWeekends(string $startDate, string $endDate): float
    {
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
        
        $workingDays = 0;
        $current = $start;
        
        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N'); // 1 (Monday) to 7 (Sunday)
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $workingDays++;
            }
            $current = $current->modify('+1 day');
        }
        
        return $workingDays;
    }
}
