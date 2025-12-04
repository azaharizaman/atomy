<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Request DTO for hiring workflow.
 * 
 * Following Advanced Orchestrator Pattern: strict contracts instead of arrays.
 */
final readonly class HiringRequest
{
    public function __construct(
        public string $applicationId,
        public string $jobPostingId,
        public bool $hired,
        public string $decidedBy,
        public ?string $startDate = null,
        public ?string $positionId = null,
        public ?string $departmentId = null,
        public ?string $reportsTo = null,
        public ?array $metadata = null,
    ) {}
}
