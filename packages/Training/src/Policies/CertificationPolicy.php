<?php

declare(strict_types=1);

namespace Nexus\Training\Policies;

final readonly class CertificationPolicy
{
    public function requiresCertification(string $courseId): bool
    {
        return false;
    }
}
