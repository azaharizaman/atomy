<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;

class ScalarMetricCalculatorService
{
    public function __construct(
        private readonly NumericValueService $numericService
    ) {}

    /** @param list<int|float|string> $values */
    public function sum(array $values, PrecisionPolicy $policy): float
    {
        if ($values === []) {
            throw new InsufficientDataException('Sum requires at least one value.');
        }

        $result = 0.0;
        foreach ($values as $value) {
            $result += $this->numericService->normalize($value);
        }

        return $this->numericService->round($result, $policy);
    }

    /** @param list<int|float|string> $values */
    public function avg(array $values, PrecisionPolicy $policy): float
    {
        if ($values === []) {
            throw new InsufficientDataException('Average requires at least one value.');
        }

        $sum = 0.0;
        foreach ($values as $value) {
            $sum += $this->numericService->normalize($value);
        }

        return $this->numericService->round($sum / count($values), $policy);
    }

    /** @param list<int|float|string> $values */
    public function min(array $values, PrecisionPolicy $policy): float
    {
        if ($values === []) {
            throw new InsufficientDataException('Min requires at least one value.');
        }

        $normalized = array_map(
            fn ($v) => $this->numericService->normalize($v),
            $values
        );

        return $this->numericService->round(min($normalized), $policy);
    }

    /** @param list<int|float|string> $values */
    public function max(array $values, PrecisionPolicy $policy): float
    {
        if ($values === []) {
            throw new InsufficientDataException('Max requires at least one value.');
        }

        $normalized = array_map(
            fn ($v) => $this->numericService->normalize($v),
            $values
        );

        return $this->numericService->round(max($normalized), $policy);
    }

    /** @param list<int|float|string> $values */
    public function count(array $values, PrecisionPolicy $policy): float
    {
        if ($values === []) {
            throw new InsufficientDataException('Count requires at least one value.');
        }

        return $this->numericService->round((float) count($values), $policy);
    }

    public function ratio(int|float|string $numerator, int|float|string $denominator, PrecisionPolicy $policy): float
    {
        $den = $this->numericService->normalize($denominator);

        if ($den === 0.0) {
            throw new DivideByZeroMetricException();
        }

        $num = $this->numericService->normalize($numerator);

        return $this->numericService->round($num / $den, $policy);
    }

    public function delta(int|float|string $left, int|float|string $right, PrecisionPolicy $policy): float
    {
        $l = $this->numericService->normalize($left);
        $r = $this->numericService->normalize($right);

        return $this->numericService->round($l - $r, $policy);
    }

    public function absoluteDelta(int|float|string $left, int|float|string $right, PrecisionPolicy $policy): float
    {
        $l = $this->numericService->normalize($left);
        $r = $this->numericService->normalize($right);

        return $this->numericService->round(abs($l - $r), $policy);
    }

    public function pctChange(int|float|string $current, int|float|string $previous, PrecisionPolicy $policy): float
    {
        $prev = $this->numericService->normalize($previous);

        if ($prev === 0.0) {
            throw new DivideByZeroMetricException();
        }

        $curr = $this->numericService->normalize($current);

        return $this->numericService->round(($curr - $prev) / $prev, $policy);
    }

    /**
     * @param list<int|float|string> $values
     * @param list<int|float|string> $weights
     */
    public function weightedAvg(array $values, array $weights, PrecisionPolicy $policy): float
    {
        if (count($values) !== count($weights)) {
            throw new FormulaValidationException('Weighted calculation requires the same number of values and weights.');
        }

        if ($values === []) {
            throw new InsufficientDataException('Weighted average requires at least one value.');
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($values as $i => $value) {
            $v = $this->numericService->normalize($value);
            $w = $this->numericService->normalize($weights[$i]);
            $weightedSum += $v * $w;
            $weightTotal += $w;
        }

        if ($weightTotal === 0.0) {
            throw new DivideByZeroMetricException();
        }

        return $this->numericService->round($weightedSum / $weightTotal, $policy);
    }

    /**
     * @param list<int|float|string> $values
     * @param list<int|float|string> $weights
     */
    public function weightedScore(array $values, array $weights, PrecisionPolicy $policy): float
    {
        return $this->weightedAvg($values, $weights, $policy);
    }
}
