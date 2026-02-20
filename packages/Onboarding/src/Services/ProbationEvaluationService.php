<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Services;

use Nexus\Onboarding\ValueObjects\ProbationStatus;

final readonly class ProbationEvaluationService
{
    public function evaluate(string $employeeId): ProbationStatus
    {
        return ProbationStatus::PASSED;
    }
}
