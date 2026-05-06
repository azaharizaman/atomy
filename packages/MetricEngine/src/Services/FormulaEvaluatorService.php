<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaEvaluatorInterface;
use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\ComparisonType;
use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Enums\ValueType;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\Exceptions\TypeMismatchException;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricResult;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

class FormulaEvaluatorService implements FormulaEvaluatorInterface
{
    public function __construct(
        private readonly ScalarMetricCalculatorService $calculator,
        private readonly TimeSeriesMetricCalculatorService $timeSeriesCalculator
    ) {}

    /** @param array<string, MetricInput|MetricSeries> $inputs */
    public function evaluate(FormulaInterface $formula, array $inputs): MetricResult
    {
        $resolvedOperands = $this->resolveOperands($formula->operands(), $inputs);
        $unit = $this->resolveUnit($formula->operands(), $inputs);

        return match ($formula->operation()) {
            AggregationType::ROLLING_SUM,
            AggregationType::ROLLING_AVG => $this->evaluateRollingFormula($formula, $resolvedOperands, $unit),
            AggregationType::PERIOD_COMPARE => $this->evaluatePeriodCompareFormula($formula, $resolvedOperands, $unit),
            default => $this->evaluateScalarFormula($formula, $resolvedOperands, $unit),
        };
    }

    /**
     * @param list<mixed> $resolvedOperands
     */
    private function evaluateScalarFormula(FormulaInterface $formula, array $resolvedOperands, ?string $unit): MetricResult
    {
        $this->rejectSeriesOperands($formula, $resolvedOperands);
        $value = $this->dispatchScalarOperation($formula, $resolvedOperands);

        return new MetricResult(
            formulaIdentifier: $formula->identifier(),
            value: $value,
            valueType: ValueType::NUMBER,
            inputMode: InputMode::SCALAR,
            precisionPolicy: $formula->precisionPolicy(),
            unit: $unit
        );
    }

    /**
     * @param list<mixed> $operands
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @return list<mixed>
     */
    private function resolveOperands(array $operands, array $inputs): array
    {
        $resolved = [];

        foreach ($operands as $operand) {
            $resolved[] = $this->resolveOperand($operand, $inputs);
        }

        return $resolved;
    }

    /** @param array<string, MetricInput|MetricSeries> $inputs */
    private function resolveOperand(mixed $operand, array $inputs): mixed
    {
        if ($operand instanceof FormulaInterface) {
            return $this->evaluate($operand, $inputs)->value();
        }

        if ($operand instanceof FormulaReference) {
            if (! isset($inputs[$operand->identifier])) {
                throw new MissingInputException($operand->identifier);
            }

            $input = $inputs[$operand->identifier];

            if ($input instanceof MetricSeries) {
                return $input;
            }

            return $input->value;
        }

        if (is_array($operand)) {
            return array_map(
                fn (mixed $nestedOperand): mixed => $this->resolveOperand($nestedOperand, $inputs),
                $operand
            );
        }

        if (! is_string($operand)) {
            return $operand;
        }

        if (! isset($inputs[$operand])) {
            throw new MissingInputException($operand);
        }

        $input = $inputs[$operand];

        if ($input instanceof MetricSeries) {
            return $input;
        }

        return $input->value;
    }

    /**
     * @param list<mixed> $operands
     */
    private function dispatchScalarOperation(FormulaInterface $formula, array $operands): float
    {
        $policy = $formula->precisionPolicy();

        return match ($formula->operation()) {
            AggregationType::SUM => $this->calculator->sum($this->requireNumericOperands($formula, $operands), $policy),
            AggregationType::AVG => $this->calculator->avg($this->requireNumericOperands($formula, $operands), $policy),
            AggregationType::MIN => $this->calculator->min($this->requireNumericOperands($formula, $operands), $policy),
            AggregationType::MAX => $this->calculator->max($this->requireNumericOperands($formula, $operands), $policy),
            AggregationType::COUNT => $this->calculator->count($this->requireNumericOperands($formula, $operands), $policy),
            AggregationType::RATIO => $this->calculator->ratio(
                $this->requireNumericOperand($formula, $operands, 0),
                $this->requireNumericOperand($formula, $operands, 1),
                $policy
            ),
            AggregationType::DELTA => $this->calculator->delta(
                $this->requireNumericOperand($formula, $operands, 0),
                $this->requireNumericOperand($formula, $operands, 1),
                $policy
            ),
            AggregationType::ABSOLUTE_DELTA => $this->calculator->absoluteDelta(
                $this->requireNumericOperand($formula, $operands, 0),
                $this->requireNumericOperand($formula, $operands, 1),
                $policy
            ),
            AggregationType::PCT_CHANGE => $this->calculator->pctChange(
                $this->requireNumericOperand($formula, $operands, 0),
                $this->requireNumericOperand($formula, $operands, 1),
                $policy
            ),
            AggregationType::WEIGHTED_AVG => $this->calculator->weightedAvg(
                ...$this->requireWeightedOperands($formula, $operands)
            ),
            AggregationType::WEIGHTED_SCORE => $this->calculator->weightedScore(
                ...$this->requireWeightedOperands($formula, $operands)
            ),
            default => throw new FormulaValidationException(
                "Unsupported aggregation type [{$formula->operation()->value}]."
            ),
        };
    }

    /**
     * @param list<mixed> $operands
     */
    private function evaluateRollingFormula(FormulaInterface $formula, array $operands, ?string $unit): MetricResult
    {
        $this->requireExactOperandCount($formula, $operands, 1);
        $series = $operands[0];

        if (! $series instanceof MetricSeries) {
            throw new TypeMismatchException("Operation [{$formula->operation()->value}] requires a time-series input.");
        }

        $window = $formula->window();

        if ($window === null) {
            throw new FormulaValidationException("Operation [{$formula->operation()->value}] requires a time window.");
        }

        $value = match ($formula->operation()) {
            AggregationType::ROLLING_SUM => $this->timeSeriesCalculator
                ->rollingSum($series, $window, $formula->precisionPolicy()),
            AggregationType::ROLLING_AVG => $this->timeSeriesCalculator
                ->rollingAvg($series, $window, $formula->precisionPolicy()),
            default => throw new FormulaValidationException(
                "Unsupported time-series aggregation type [{$formula->operation()->value}]."
            ),
        };

        return new MetricResult(
            formulaIdentifier: $formula->identifier(),
            value: $value,
            valueType: ValueType::NUMBER,
            inputMode: InputMode::TIME_SERIES,
            precisionPolicy: $formula->precisionPolicy(),
            unit: $unit,
            window: $window
        );
    }

    /**
     * @param list<mixed> $operands
     */
    private function evaluatePeriodCompareFormula(FormulaInterface $formula, array $operands, ?string $unit): MetricResult
    {
        $currentValue = $this->requireNumericOperand($formula, $operands, 0);
        $previousValue = $this->requireNumericOperand($formula, $operands, 1);

        if ($formula->comparison()?->type !== ComparisonType::PREVIOUS_PERIOD) {
            throw new FormulaValidationException('Operation [period_compare] requires a previous-period comparison definition.');
        }

        $result = $this->timeSeriesCalculator
            ->periodCompare($currentValue, $previousValue, $formula->precisionPolicy());

        return new MetricResult(
            formulaIdentifier: $formula->identifier(),
            value: [
                'delta' => $result->delta,
                'percentChange' => $result->percentChange,
            ],
            valueType: ValueType::COMPARISON,
            inputMode: InputMode::SCALAR,
            precisionPolicy: $formula->precisionPolicy(),
            unit: $unit,
            comparison: $formula->comparison()
        );
    }

    /**
     * @param list<mixed> $operands
     */
    private function requireExactOperandCount(FormulaInterface $formula, array $operands, int $count): void
    {
        if (count($operands) !== $count) {
            throw new FormulaValidationException("Operation [{$formula->operation()->value}] requires exactly {$count} operands.");
        }
    }

    /**
     * @param list<mixed> $operands
     */
    private function requireNumericOperands(FormulaInterface $formula, array $operands): array
    {
        foreach ($operands as $operand) {
            if (! is_int($operand) && ! is_float($operand) && ! is_string($operand)) {
                throw new TypeMismatchException("Operation [{$formula->operation()->value}] requires scalar numeric operands.");
            }
        }

        return $operands;
    }

    /**
     * @param list<mixed> $operands
     */
    private function requireNumericOperand(FormulaInterface $formula, array $operands, int $index): int|float|string
    {
        $this->requireExactOperandCount($formula, $operands, 2);

        $operand = $operands[$index];

        if (! is_int($operand) && ! is_float($operand) && ! is_string($operand)) {
            throw new TypeMismatchException("Operation [{$formula->operation()->value}] requires scalar numeric operands.");
        }

        return $operand;
    }

    /**
     * @param list<mixed> $operands
     * @return array{0: list<int|float|string>, 1: list<int|float|string>, 2: \Nexus\MetricEngine\ValueObjects\PrecisionPolicy}
     */
    private function requireWeightedOperands(FormulaInterface $formula, array $operands): array
    {
        $this->requireExactOperandCount($formula, $operands, 2);
        [$values, $weights] = $operands;

        if (! is_array($values) || ! is_array($weights)) {
            throw new FormulaValidationException("Operation [{$formula->operation()->value}] requires value and weight arrays.");
        }

        foreach ([$values, $weights] as $operandList) {
            foreach ($operandList as $operand) {
                if (! is_int($operand) && ! is_float($operand) && ! is_string($operand)) {
                    throw new TypeMismatchException("Operation [{$formula->operation()->value}] requires numeric value and weight arrays.");
                }
            }
        }

        return [$values, $weights, $formula->precisionPolicy()];
    }

    /**
     * @param list<mixed> $operands
     */
    private function rejectSeriesOperands(FormulaInterface $formula, array $operands): void
    {
        foreach ($operands as $operand) {
            if ($operand instanceof MetricSeries) {
                throw new TypeMismatchException("Scalar operation [{$formula->operation()->value}] received a time-series input.");
            }
        }
    }

    /** @param list<mixed> $operands */
    private function resolveUnit(array $operands, array $inputs): ?string
    {
        foreach ($operands as $operand) {
            if ($operand instanceof FormulaInterface) {
                continue;
            }

            if (is_array($operand)) {
                $unit = $this->resolveUnit($operand, $inputs);

                if ($unit !== null) {
                    return $unit;
                }

                continue;
            }

            if (! is_string($operand) || ! isset($inputs[$operand])) {
                continue;
            }

            return $inputs[$operand]->unit;
        }

        return null;
    }

}
