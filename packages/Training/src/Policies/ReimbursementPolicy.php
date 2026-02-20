<?php

declare(strict_types=1);

namespace Nexus\Training\Policies;

final readonly class ReimbursementPolicy
{
    public function isReimbursable(string $courseId): bool
    {
        return false;
    }
}
