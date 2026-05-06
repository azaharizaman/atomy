<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\ValueObjects\BandDefinition;
use Nexus\MetricEngine\ValueObjects\BandedScore;

class BandedScoreService
{
    public function __construct(
        private readonly NumericValueService $numericValueService
    ) {}

    /** @param list<BandDefinition> $bands */
    public function score(int|float|string $value, array $bands): BandedScore
    {
        $score = $this->numericValueService->normalize($value);

        foreach ($bands as $band) {
            $minimum = $this->numericValueService->normalize($band->minimum);
            $maximum = $this->numericValueService->normalize($band->maximum);

            if ($minimum > $maximum) {
                throw new FormulaValidationException("Band [{$band->label}] minimum must be less than or equal to maximum.");
            }

            if ($score >= $minimum && $score <= $maximum) {
                return new BandedScore($score, $band->label);
            }
        }

        throw new FormulaValidationException("Score [{$value}] does not match any supplied band.");
    }
}
