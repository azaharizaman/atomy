<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\PerformanceReview;

use Nexus\PerformanceReview\Contracts\AppraisalRepositoryInterface;

final readonly class StartAppraisalCycleHandler
{
    public function __construct(
        private AppraisalRepositoryInterface $appraisalRepository
    ) {}
    
    public function handle(array $cycleData): void
    {
        // Start new appraisal cycle for employees
        throw new \RuntimeException('Implementation pending');
    }
}
