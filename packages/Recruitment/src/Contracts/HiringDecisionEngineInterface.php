<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Contracts;

use Nexus\Recruitment\Entities\Applicant;

interface HiringDecisionEngineInterface
{
    public function shouldHire(Applicant $applicant): bool;
    public function calculateFitScore(Applicant $applicant): float;
}
