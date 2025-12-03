<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Recruitment;

use Nexus\Recruitment\Contracts\HiringDecisionServiceInterface;

final readonly class MakeHiringDecisionHandler
{
    public function __construct(
        private HiringDecisionServiceInterface $hiringDecisionService
    ) {}
    
    public function handle(string $applicationId, bool $hired): void
    {
        // Make final hiring decision
        throw new \RuntimeException('Implementation pending');
    }
}
