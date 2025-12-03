<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\PerformanceReview;

use Nexus\PerformanceReview\Contracts\AppraisalRepositoryInterface;

final readonly class SubmitSelfReviewHandler
{
    public function __construct(
        private AppraisalRepositoryInterface $appraisalRepository
    ) {}
    
    public function handle(string $appraisalId, array $selfReviewData): void
    {
        // Submit employee self-review
        throw new \RuntimeException('Implementation pending');
    }
}
