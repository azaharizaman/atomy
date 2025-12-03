<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Services;

use Nexus\TrainingManagement\ValueObjects\Certification;

final readonly class CertificationGrantService
{
    public function grant(string $courseName): Certification
    {
        return new Certification(
            $courseName,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+1 year')
        );
    }
}
