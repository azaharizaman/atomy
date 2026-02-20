<?php

declare(strict_types=1);

namespace Nexus\Training\Contracts;

interface TrainingPolicyInterface
{
    public function requiresApproval(string $courseId): bool;
    public function isEligible(string $employeeId, string $courseId): bool;
}
