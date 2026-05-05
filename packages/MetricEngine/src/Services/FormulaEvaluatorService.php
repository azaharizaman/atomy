<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaEvaluatorInterface;
use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Enums\ValueType;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\Exceptions\TypeMismatchException;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricResult;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

class FormulaEvaluatorService implements FormulaEvaluatorInterface
{
    public function __construct(
        private readonly ScalarMetricCalculatorService $calculator
    ) {}

    /** @param array<string, MetricInput|MetricSeries> $inputs */
    public function evaluate(FormulaInterface $formula, array $inputs): MetricResult
    {
        $resolvedOperands = $this->resolveOperands($formula->operands(), $inputs);

        $value = $this->dispatchOperation($formula, $resolvedOperands);

        return new MetricResult(
            formulaIdentifier: $formula->identifier(),
            value: $value,
            valueType: ValueType::NUMBER,
            inputMode: InputMode::SCALAR,
            precisionPolicy: $formula->precisionPolicy()
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
            if ($operand instanceof FormulaInterface) {
                $resolved[] = $this->evaluate($operand, $inputs)->value();
            } elseif (is_string($operand)) {
                if (! isset($inputs[$operand])) {
                    throw new MissingInputException($operand);
                }

                $input = $inputs[$operand];

                if ($input instanceof MetricSeries) {
                    throw new TypeMismatchException("Scalar operation received series input [{$operand}].");
                }

                $resolved[] = $input->value;
            } else {
                $resolved[] = $operand;
            }
        }

        return $resolved;
    }

    /**
     * @param list<mixed> $operands
     */
    private function dispatchOperation(FormulaInterface $formula, array $operands): float
    {
        $policy = $formula->precisionPolicy();

        return match ($formula->operation()) {
            \Nexus\MetricEngine\Enums\AggregationType::SUM => $this->calculator->sum($operands, $policy),
            \Nexus\MetricEngine\Enums\AggregationType::AVG => $this->calculator->avg($operands, $policy),
            \Nexus\MetricEngine\Enums\AggregationType::MIN => $this->calculator->min($operands, $policy),
            \Nexus\MetricEngine\Enums\AggregationType::MAX => $this->calculator->max($operands, $policy),
            \Nexus\MetricEngine\Enums\AggregationType::COUNT => $this->calculator->count($operands, $policy),
            \Nexus\MetricEngine\Enums\AggregationType::RATIO => $this->calculator->ratio($operands[0], $operands[1], $policy),
            \Nexus\MetricEngine\Enums\AggregationType::DELTA => $this->calculator->delta($operands[0], $operands[1], $policy),
            \Nexus\MetricEngine\Enums\AggregationType::ABSOLUTE_DELTA => $this->calculator->absoluteDelta($operands[0], $operands[1], $policy),
            \Nexus\MetricEngine\Enums\AggregationType::PCT_CHANGE => $this->calculator->pctChange($operands[0], $operands[1], $policy),
            \Nexus\MetricEngine\Enums\AggregationType::WEIGHTED_AVG => $this->calculator->weightedAvg($operands[0], $operands[1], $policy),
            \Nexus\MetricEngine\Enums\AggregationType::WEIGHTED_SCORE => $this->calculator->weightedScore($operands[0], $operands[1], $policy),
            default => throw new \InvalidArgumentException("Unsupported aggregation type: {$formula->operation()->value}"),
        };
    }
}
