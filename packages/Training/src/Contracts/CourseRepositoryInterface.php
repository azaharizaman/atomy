<?php

declare(strict_types=1);

namespace Nexus\Training\Contracts;

use Nexus\Training\Entities\Course;

interface CourseRepositoryInterface
{
    public function findById(string $id): ?Course;
    public function findActive(): array;
    public function save(Course $course): void;
}
