<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Context DTO aggregating application and candidate data.
 * 
 * Data Providers aggregate data from multiple packages into this single object.
 */
final readonly class ApplicationContext
{
    public function __construct(
        public string $applicationId,
        public string $candidateName,
        public string $candidateEmail,
        public string $jobPostingId,
        public string $positionTitle,
        public string $departmentId,
        public string $departmentName,
        public string $status,
        public ?array $interviewResults = null,
        public ?array $qualifications = null,
        public ?array $metadata = null,
    ) {}
}
