<?php

declare(strict_types=1);

namespace Nexus\LeaveManagement\Services\AccrualStrategies;

use Nexus\LeaveManagement\Contracts\AccrualStrategyInterface;

final readonly class MonthlyAccrualStrategy implements AccrualStrategyInterface
{
    public function calculate(string $employeeId, string $leaveTypeId, \DateTimeImmutable $asOfDate): float
    {
        // TODO: Implement monthly accrual logic
        return 0.0;
    }

    public function getName(): string
    {
        return 'monthly';
    }
}
