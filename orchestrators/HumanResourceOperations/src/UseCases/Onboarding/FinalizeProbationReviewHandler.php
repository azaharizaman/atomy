<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Onboarding;

use Nexus\Onboarding\Services\ProbationReviewService;

final readonly class FinalizeProbationReviewHandler
{
    public function __construct(
        private ProbationReviewService $probationReviewService
    ) {}
    
    public function handle(string $employeeId, bool $confirmed): void
    {
        // Finalize probation review decision
        throw new \RuntimeException('Implementation pending');
    }
}
