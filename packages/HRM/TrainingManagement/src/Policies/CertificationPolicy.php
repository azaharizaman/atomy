<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Policies;

final readonly class CertificationPolicy
{
    public function requiresCertification(string $courseId): bool
    {
        return false;
    }
}
