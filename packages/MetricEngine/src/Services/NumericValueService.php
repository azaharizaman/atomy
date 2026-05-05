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
        return round($value, $policy->scale, PHP_ROUND_HALF_UP);
    }
}
