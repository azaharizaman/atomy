<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;

/**
 * Detects overlapping fiscal period dates.
 */
final class FiscalPeriodOverlapRule implements RuleValidationInterface
{
    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context)) {
            return RuleValidationResult::failure('Invalid context');
        }

        $newStartDate = $context['startDate'] ?? null;
        $newEndDate = $context['endDate'] ?? null;
        $existingPeriods = $context['existingPeriods'] ?? [];

        if (!$newStartDate || !$newEndDate) {
            return RuleValidationResult::failure('Start date and end date are required');
        }

        $newStart = $newStartDate instanceof \DateTimeInterface 
            ? $newStartDate 
            : new \DateTime($newStartDate);
        $newEnd = $newEndDate instanceof \DateTimeInterface 
            ? $newEndDate 
            : new \DateTime($newEndDate);

        $overlaps = [];

        foreach ($existingPeriods as $period) {
            $existingStart = $period['startDate'] instanceof \DateTimeInterface
                ? $period['startDate']
                : new \DateTime($period['startDate']);
            $existingEnd = $period['endDate'] instanceof \DateTimeInterface
                ? $period['endDate']
                : new \DateTime($period['endDate']);

            // Check for overlap
            if ($newStart <= $existingEnd && $newEnd >= $existingStart) {
                $overlaps[] = [
                    'period_id' => $period['id'] ?? 'unknown',
                    'period_name' => $period['name'] ?? 'unknown',
                    'start_date' => $existingStart->format('Y-m-d'),
                    'end_date' => $existingEnd->format('Y-m-d'),
                ];
            }
        }

        if (!empty($overlaps)) {
            return RuleValidationResult::failure(
                'New period overlaps with existing periods',
                ['overlapping_periods' => $overlaps]
            );
        }

        return RuleValidationResult::success();
    }
}
