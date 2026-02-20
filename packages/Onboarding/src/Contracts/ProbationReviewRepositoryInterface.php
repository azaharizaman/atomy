<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Contracts;

use Nexus\Onboarding\Entities\ProbationReview;

interface ProbationReviewRepositoryInterface
{
    public function findById(string $id): ?ProbationReview;
    public function findByEmployeeId(string $employeeId): array;
    public function save(ProbationReview $review): void;
}
