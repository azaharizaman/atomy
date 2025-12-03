<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Recruitment;

use Nexus\Recruitment\Contracts\ApplicationRepositoryInterface;

final readonly class SubmitApplicationHandler
{
    public function __construct(
        private ApplicationRepositoryInterface $applicationRepository
    ) {}
    
    public function handle(string $jobId, array $applicantData): void
    {
        // Submit job application
        throw new \RuntimeException('Implementation pending');
    }
}
