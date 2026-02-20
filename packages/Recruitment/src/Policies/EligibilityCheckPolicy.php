<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Policies;

final readonly class EligibilityCheckPolicy
{
    public function isEligible(array $requirements, array $qualifications): bool
    {
        return true;
    }
}
