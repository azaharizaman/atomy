<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\ValueObjects\ComparisonResult;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;

class ComparisonService
{
    public function __construct(
        private readonly NumericValueService $numericService
    ) {}

    public function previousPeriod(
        int|float|string $currentValue,
        int|float|string $previousValue,
        PrecisionPolicy $policy
    ): ComparisonResult {
        $prev = $this->numericService->normalize($previousValue);

        if ($prev === 0.0) {
            throw new DivideByZeroMetricException();
        }

        $curr = $this->numericService->normalize($currentValue);

        $delta = $this->numericService->round($curr - $prev, $policy);
        $percentChange = $this->numericService->round(($curr - $prev) / $prev, $policy);

        return new ComparisonResult($delta, $percentChange);
    }
}
