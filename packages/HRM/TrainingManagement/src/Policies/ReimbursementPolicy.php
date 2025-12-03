<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Policies;

final readonly class ReimbursementPolicy
{
    public function isReimbursable(string $courseId): bool
    {
        return false;
    }
}
