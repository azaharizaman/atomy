<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Policies;

use Nexus\TrainingManagement\Contracts\TrainingPolicyInterface;

final readonly class TrainingApprovalPolicy implements TrainingPolicyInterface
{
    public function requiresApproval(string $courseId): bool
    {
        return true;
    }
    
    public function isEligible(string $employeeId, string $courseId): bool
    {
        return true;
    }
}
