<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Services;

use Nexus\Disciplinary\Entities\DisciplinaryCase;

final readonly class SeverityScoringService
{
    public function calculateSeverityScore(DisciplinaryCase $case): int
    {
        // Returns score from 1-10
        return 5;
    }
}
