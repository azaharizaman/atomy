<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Services;

use Nexus\Recruitment\Entities\Interview;
use Nexus\Recruitment\ValueObjects\InterviewResult;

final readonly class InterviewEvaluationService
{
    public function evaluate(Interview $interview): InterviewResult
    {
        return InterviewResult::GOOD;
    }
}
