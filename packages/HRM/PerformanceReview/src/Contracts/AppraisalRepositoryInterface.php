<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Contracts;

use Nexus\PerformanceReview\Entities\Appraisal;

interface AppraisalRepositoryInterface
{
    public function findById(string $id): ?Appraisal;
    public function findByEmployeeId(string $employeeId): array;
    public function save(Appraisal $appraisal): void;
}
