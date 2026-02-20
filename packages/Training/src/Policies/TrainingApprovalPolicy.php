<?php

declare(strict_types=1);

namespace Nexus\Training\Policies;

use Nexus\Training\Contracts\TrainingPolicyInterface;

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
