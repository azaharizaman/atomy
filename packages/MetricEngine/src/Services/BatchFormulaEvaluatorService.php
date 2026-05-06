<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Exceptions\MetricEngineException;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\MetricAuditTrace;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationBatchResult;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationOptions;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationOutcome;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

class BatchFormulaEvaluatorService
{
    public function __construct(
        private readonly FormulaEvaluatorService $formulaEvaluator,
        private readonly FormulaGraphService $graphService,
        private readonly MetricStatusInferenceService $statusInference
    ) {}

    /**
     * @param FormulaCatalog|list<FormulaInterface> $formulas
     * @param array<string, MetricInput|MetricSeries> $inputs
     */
    public function evaluate(FormulaCatalog|array $formulas, array $inputs, MetricEvaluationOptions $options = new MetricEvaluationOptions()): MetricEvaluationBatchResult
    {
        $catalog = $formulas instanceof FormulaCatalog ? $formulas : new FormulaCatalog($formulas);
        $graph = $this->graphService->build($catalog);
        $outcomes = [];
        $runtimeInputs = $inputs;

        foreach ($graph->orderedFormulaIds() as $formulaIdentifier) {
            $unavailableDependency = $this->firstUnavailableDependency($graph->dependenciesFor($formulaIdentifier), $outcomes);

            if ($unavailableDependency !== null) {
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::dependencyUnavailable(
                    $formulaIdentifier,
                    $unavailableDependency,
                    $options->includeAuditTrace ? $this->createAuditTrace(
                        $formulaIdentifier,
                        $catalog->get($formulaIdentifier)->operation()->value,
                        $catalog->get($formulaIdentifier)->operands(),
                        $inputs,
                        [],
                        [],
                        null,
                        MetricResultStatus::NOT_AVAILABLE->value,
                        'dependency_not_available',
                        "Formula [{$formulaIdentifier}] depends on unavailable formula [{$unavailableDependency}]."
                    ) : null
                );
                continue;
            }

            $formula = $catalog->get($formulaIdentifier);

            try {
                $result = $this->formulaEvaluator->evaluate($formula, $runtimeInputs);
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::available(
                    $result,
                    $options->includeAuditTrace ? $this->createAuditTrace(
                        $formulaIdentifier,
                        $formula->operation()->value,
                        $formula->operands(),
                        $inputs,
                        $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes),
                        $this->resolveAuditOperands(
                            $formula->operands(),
                            $inputs,
                            $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes)
                        ),
                        $result->value(),
                        MetricResultStatus::AVAILABLE->value,
                        null,
                        null
                    ) : null
                );

                if (! is_int($result->value()) && ! is_float($result->value()) && ! is_string($result->value())) {
                    continue;
                }

                $runtimeInputs[$formulaIdentifier] = new MetricInput($formulaIdentifier, $result->value(), $result->unit());
            } catch (\Throwable $error) {
                $status = $this->statusInference->infer($error);

                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::unavailable(
                    $formulaIdentifier,
                    $status,
                    $error,
                    $options->includeAuditTrace ? $this->createAuditTrace(
                        $formulaIdentifier,
                        $formula->operation()->value,
                        $formula->operands(),
                        $inputs,
                        $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes),
                        $this->resolveAuditOperands(
                            $formula->operands(),
                            $inputs,
                            $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes)
                        ),
                        null,
                        $status->value,
                        $error instanceof MetricEngineException ? $error->errorCode() : 'unexpected_error',
                        $error->getMessage()
                    ) : null
                );
            }
        }

        return new MetricEvaluationBatchResult($outcomes);
    }

    /**
     * @param list<string> $dependencies
     * @param array<string, MetricEvaluationOutcome> $outcomes
     */
    private function firstUnavailableDependency(array $dependencies, array $outcomes): ?string
    {
        foreach ($dependencies as $dependency) {
            if (! isset($outcomes[$dependency]) || $outcomes[$dependency]->status !== MetricResultStatus::AVAILABLE) {
                return $dependency;
            }
        }

        return null;
    }

    /**
     * @param list<string> $dependencies
     * @param array<string, MetricEvaluationOutcome> $outcomes
     * @return array<string, mixed>
     */
    private function dependencyResults(array $dependencies, array $outcomes): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (isset($outcomes[$dependency]) && $outcomes[$dependency]->result !== null) {
                $results[$dependency] = $outcomes[$dependency]->result->value();
            }
        }

        return $results;
    }

    /**
     * @param list<mixed> $operands
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @param array<string, mixed> $dependencyResults
     * @param list<mixed> $resolvedOperands
     */
    private function createAuditTrace(
        string $formulaIdentifier,
        string $operation,
        array $operands,
        array $inputs,
        array $dependencyResults,
        array $resolvedOperands,
        mixed $resultValue,
        string $status,
        ?string $reasonCode = null,
        ?string $message = null
    ): MetricAuditTrace {
        return new MetricAuditTrace(
            formulaIdentifier: $formulaIdentifier,
            operation: $operation,
            operands: $operands,
            resolvedOperands: $resolvedOperands,
            inputs: $this->usedInputValues($operands, $inputs),
            dependencyResults: $dependencyResults,
            excludedValues: [],
            resultValue: $resultValue,
            status: $status,
            reasonCode: $reasonCode,
            message: $message
        );
    }

    /**
     * @param list<mixed> $operands
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @return array<string, mixed>
     */
    private function usedInputValues(array $operands, array $inputs): array
    {
        $used = [];

        foreach ($this->collectInputNames($operands) as $name) {
            if (! isset($inputs[$name])) {
                continue;
            }

            $input = $inputs[$name];

            $used[$name] = $input instanceof MetricInput
                ? ['value' => $input->value, 'unit' => $input->unit]
                : [
                    'unit' => $input->unit,
                    'points' => array_map(
                        static fn ($point): array => [
                            'period_key' => $point->periodKey,
                            'value' => $point->value,
                            'metadata' => $point->metadata,
                        ],
                        $input->points
                    ),
                ];
        }

        return $used;
    }

    /**
     * @param list<mixed> $operands
     * @return list<string>
     */
    private function collectInputNames(array $operands): array
    {
        $names = [];

        foreach ($operands as $operand) {
            if (is_string($operand)) {
                $names[] = $operand;
                continue;
            }

            if ($operand instanceof FormulaReference) {
                continue;
            }

            if ($operand instanceof FormulaDefinition) {
                $names = array_merge($names, $this->collectInputNames($operand->operands()));
                continue;
            }

            if (is_array($operand)) {
                $names = array_merge($names, $this->collectInputNames($operand));
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param list<mixed> $operands
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @param array<string, mixed> $dependencyResults
     * @return list<mixed>
     */
    private function resolveAuditOperands(array $operands, array $inputs, array $dependencyResults): array
    {
        return array_map(
            fn (mixed $operand): mixed => $this->resolveAuditOperand($operand, $inputs, $dependencyResults),
            $operands
        );
    }

    /** @param array<string, MetricInput|MetricSeries> $inputs */
    private function resolveAuditOperand(mixed $operand, array $inputs, array $dependencyResults): mixed
    {
        if ($operand instanceof FormulaReference) {
            return $dependencyResults[$operand->identifier] ?? null;
        }

        if ($operand instanceof FormulaDefinition) {
            return $this->formulaEvaluator->evaluate($operand, $inputs)->value();
        }

        if (is_array($operand)) {
            return array_map(
                fn (mixed $nestedOperand): mixed => $this->resolveAuditOperand($nestedOperand, $inputs, $dependencyResults),
                $operand
            );
        }

        if (! is_string($operand) || ! isset($inputs[$operand])) {
            return $operand;
        }

        $input = $inputs[$operand];

        return $input instanceof MetricInput ? $input->value : $input;
    }
}
