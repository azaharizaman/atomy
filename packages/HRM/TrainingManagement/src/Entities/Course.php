<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Entities;

use Nexus\TrainingManagement\ValueObjects\CourseId;

final readonly class Course
{
    public function __construct(
        public CourseId $id,
        public string $name,
        public string $description,
        public int $durationHours,
        public bool $isMandatory = false,
        public bool $isActive = true,
    ) {}
}
