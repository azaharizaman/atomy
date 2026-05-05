<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\ValueObjects\ComparisonResult;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

class TimeSeriesMetricCalculatorService
{
    public function __construct(
        private readonly NumericValueService $numericService,
        private readonly WindowResolverService $windowResolver
    ) {}

    public function rollingSum(MetricSeries $series, TimeWindow $window, PrecisionPolicy $policy): float
    {
        $resolved = $this->windowResolver->resolve($series, $window);

        $values = array_map(
            fn ($point) => $this->numericService->normalize($point->value),
            $resolved->points
        );

        if ($values === []) {
            throw new InsufficientDataException('Rolling sum requires at least one point in the resolved window.');
        }

        return $this->numericService->round(array_sum($values), $policy);
    }

    public function rollingAvg(MetricSeries $series, TimeWindow $window, PrecisionPolicy $policy): float
    {
        $resolved = $this->windowResolver->resolve($series, $window);

        $values = array_map(
            fn ($point) => $this->numericService->normalize($point->value),
            $resolved->points
        );

        if ($values === []) {
            throw new InsufficientDataException('Rolling average requires at least one point in the resolved window.');
        }

        return $this->numericService->round(array_sum($values) / count($values), $policy);
    }

    public function periodCompare(
        int|float|string $currentValue,
        int|float|string $previousValue,
        PrecisionPolicy $policy
    ): ComparisonResult {
        $comparisonService = new ComparisonService($this->numericService);

        return $comparisonService->previousPeriod($currentValue, $previousValue, $policy);
    }
}
