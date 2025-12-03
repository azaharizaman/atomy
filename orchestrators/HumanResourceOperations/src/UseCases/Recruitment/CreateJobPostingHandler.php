<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Recruitment;

use Nexus\Recruitment\Contracts\JobPostingRepositoryInterface;

final readonly class CreateJobPostingHandler
{
    public function __construct(
        private JobPostingRepositoryInterface $jobPostingRepository
    ) {}
    
    public function handle(array $jobData): void
    {
        // Create job posting
        throw new \RuntimeException('Implementation pending');
    }
}
