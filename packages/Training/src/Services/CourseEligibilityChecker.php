<?php

declare(strict_types=1);

namespace Nexus\Training\Services;

final readonly class CourseEligibilityChecker
{
    public function check(string $employeeId, string $courseId): bool
    {
        return true;
    }
}
