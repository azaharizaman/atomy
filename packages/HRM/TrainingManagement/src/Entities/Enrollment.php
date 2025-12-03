<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Entities;

use Nexus\TrainingManagement\ValueObjects\EnrollmentStatus;
use Nexus\TrainingManagement\ValueObjects\Certification;

final readonly class Enrollment
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $courseId,
        public EnrollmentStatus $status,
        public \DateTimeImmutable $enrolledAt,
        public ?\DateTimeImmutable $completedAt = null,
        public ?Certification $certification = null,
    ) {}
}
