<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Entities;

use Nexus\PerformanceReview\ValueObjects\CompetencyScore;

final readonly class Kpi
{
    public function __construct(
        public string $id,
        public string $appraisalId,
        public string $name,
        public string $description,
        public float $weight,
        public ?CompetencyScore $score = null,
    ) {}
}
