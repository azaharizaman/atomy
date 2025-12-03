<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Contracts;

use Nexus\Recruitment\Entities\JobPosting;

interface JobPostingRepositoryInterface
{
    public function findById(string $id): ?JobPosting;
    public function findActive(): array;
    public function save(JobPosting $jobPosting): void;
}
