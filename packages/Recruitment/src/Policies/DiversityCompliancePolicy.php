<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Policies;

final readonly class DiversityCompliancePolicy
{
    public function checkCompliance(array $applicantPool): bool
    {
        return true;
    }
}
