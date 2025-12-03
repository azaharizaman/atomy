<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Services;

use Nexus\Recruitment\Contracts\HiringDecisionEngineInterface;
use Nexus\Recruitment\Entities\Applicant;

final readonly class HiringDecisionEngine implements HiringDecisionEngineInterface
{
    public function shouldHire(Applicant $applicant): bool
    {
        return $this->calculateFitScore($applicant) >= 70.0;
    }
    
    public function calculateFitScore(Applicant $applicant): float
    {
        return $applicant->score?->value ?? 0.0;
    }
}
