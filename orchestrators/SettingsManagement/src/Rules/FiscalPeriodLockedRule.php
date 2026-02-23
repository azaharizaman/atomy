<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodOperationType;

/**
 * Validates that a fiscal period is not locked for the requested operation.
 */
final class FiscalPeriodLockedRule implements RuleValidationInterface
{
    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context)) {
            return RuleValidationResult::failure('Invalid context');
        }

        $periodId = $context['periodId'] ?? null;
        $operationType = $context['operationType'] ?? PeriodOperationType::TRANSACTION;
        $period = $context['period'] ?? null;

        if (!$periodId || !$period) {
            return RuleValidationResult::failure('Period ID and period data are required');
        }

        $isLocked = $period['isLocked'] ?? false;
        $isClosed = $period['status'] ?? 'open' === 'closed';

        if ($isClosed) {
            return RuleValidationResult::failure(
                "Period '{$periodId}' is closed and cannot be modified",
                ['period_id' => $periodId, 'is_closed' => true]
            );
        }

        if ($isLocked) {
            // Check if adjustment is allowed on locked period
            if ($operationType === PeriodOperationType::ADJUSTMENT) {
                $allowsAdjustment = $period['allowsAdjustment'] ?? false;
                if (!$allowsAdjustment) {
                    return RuleValidationResult::failure(
                        "Period '{$periodId}' is locked and does not allow adjustments",
                        ['period_id' => $periodId, 'is_locked' => true]
                    );
                }
            } else {
                return RuleValidationResult::failure(
                    "Period '{$periodId}' is locked",
                    ['period_id' => $periodId, 'is_locked' => true]
                );
            }
        }

        return RuleValidationResult::success();
    }
}
