<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\PerformanceReview;

use Nexus\PerformanceReview\Contracts\AppraisalRepositoryInterface;

final readonly class SubmitManagerReviewHandler
{
    public function __construct(
        private AppraisalRepositoryInterface $appraisalRepository
    ) {}
    
    public function handle(string $appraisalId, array $managerReviewData): void
    {
        // Submit manager review
        throw new \RuntimeException('Implementation pending');
    }
}
