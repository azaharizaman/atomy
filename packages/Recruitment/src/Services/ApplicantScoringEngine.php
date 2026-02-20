<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Services;

use Nexus\Recruitment\Entities\Applicant;
use Nexus\Recruitment\ValueObjects\ApplicantScore;

final readonly class ApplicantScoringEngine
{
    public function score(Applicant $applicant): ApplicantScore
    {
        return new ApplicantScore(75.0);
    }
}
