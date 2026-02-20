<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Policies;

final readonly class BackgroundCheckPolicy
{
    public function requiresBackgroundCheck(string $jobCode): bool
    {
        return false;
    }
}
