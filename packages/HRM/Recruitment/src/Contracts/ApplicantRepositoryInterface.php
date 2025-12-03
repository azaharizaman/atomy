<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Contracts;

use Nexus\Recruitment\Entities\Applicant;

interface ApplicantRepositoryInterface
{
    public function findById(string $id): ?Applicant;
    public function findByJobPostingId(string $jobPostingId): array;
    public function save(Applicant $applicant): void;
}
