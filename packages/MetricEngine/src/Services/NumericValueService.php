<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\InvalidNumericValueException;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;

class NumericValueService
{
    public function normalize(int|float|string $value): float
    {
        if (is_string($value) && ! is_numeric($value)) {
            throw new InvalidNumericValueException("Metric value [{$value}] is not numeric.");
        }

        $normalized = (float) $value;

        if (! is_finite($normalized)) {
            throw new InvalidNumericValueException('Metric value must be finite.');
        }

        return $normalized;
    }

    public function round(float $value, PrecisionPolicy $policy): float
    {
        return match ($policy->roundingMode) {
            \Nexus\MetricEngine\Enums\RoundingMode::HALF_UP => round($value, $policy->scale, PHP_ROUND_HALF_UP),
            \Nexus\MetricEngine\Enums\RoundingMode::HALF_DOWN => round($value, $policy->scale, PHP_ROUND_HALF_DOWN),
            \Nexus\MetricEngine\Enums\RoundingMode::HALF_EVEN => round($value, $policy->scale, PHP_ROUND_HALF_EVEN),
            \Nexus\MetricEngine\Enums\RoundingMode::HALF_ODD => round($value, $policy->scale, PHP_ROUND_HALF_ODD),
            \Nexus\MetricEngine\Enums\RoundingMode::TOWARD_ZERO => $this->roundTowardZero($value, $policy->scale),
            \Nexus\MetricEngine\Enums\RoundingMode::AWAY_FROM_ZERO => $this->roundAwayFromZero($value, $policy->scale),
        };
    }

    private function roundTowardZero(float $value, int $scale): float
    {
        $factor = 10 ** $scale;

        return ($value < 0 ? ceil($value * $factor) : floor($value * $factor)) / $factor;
    }

    private function roundAwayFromZero(float $value, int $scale): float
    {
        $factor = 10 ** $scale;

        return ($value < 0 ? floor($value * $factor) : ceil($value * $factor)) / $factor;
    }
}
