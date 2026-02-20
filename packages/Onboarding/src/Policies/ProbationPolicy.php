<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Policies;

final readonly class ProbationPolicy
{
    public function getProbationDurationDays(): int
    {
        return 90;
    }
}
