<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\PerformanceReview;

use Nexus\PerformanceReview\Contracts\AppraisalRepositoryInterface;

final readonly class FinalizeAppraisalHandler
{
    public function __construct(
        private AppraisalRepositoryInterface $appraisalRepository
    ) {}
    
    public function handle(string $appraisalId): void
    {
        // Finalize and lock appraisal
        throw new \RuntimeException('Implementation pending');
    }
}
