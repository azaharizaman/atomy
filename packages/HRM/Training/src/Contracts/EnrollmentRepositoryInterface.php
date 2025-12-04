<?php

declare(strict_types=1);

namespace Nexus\Training\Contracts;

use Nexus\Training\Entities\Enrollment;

interface EnrollmentRepositoryInterface
{
    public function findById(string $id): ?Enrollment;
    public function findByEmployeeId(string $employeeId): array;
    public function save(Enrollment $enrollment): void;
}
