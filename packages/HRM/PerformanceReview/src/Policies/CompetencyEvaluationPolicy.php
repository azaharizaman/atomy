<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Policies;

final readonly class CompetencyEvaluationPolicy
{
    public function getMandatoryCompetencies(): array
    {
        return ['communication', 'teamwork', 'problem_solving'];
    }
}
