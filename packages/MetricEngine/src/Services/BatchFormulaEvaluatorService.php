<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\MetricAuditTrace;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationBatchResult;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationOptions;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationOutcome;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\Exceptions\MetricEngineException;

class BatchFormulaEvaluatorService
{
    public function __construct(
        private readonly FormulaEvaluatorService $formulaEvaluator,
        private readonly FormulaGraphService $graphService,
        private readonly MetricStatusInferenceService $statusInference
    ) {}

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     */
    public function evaluate(FormulaCatalog $catalog, array $inputs, MetricEvaluationOptions $options = new MetricEvaluationOptions()): MetricEvaluationBatchResult
    {
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
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $dependencyResults
     */
    private function createAuditTrace(
        string $formulaIdentifier,
        string $operation,
        array $operands,
        array $inputs,
        array $dependencyResults,
        mixed $resultValue,
        string $status,
        ?string $reasonCode = null,
        ?string $message = null
    ): MetricAuditTrace {
        return new MetricAuditTrace(
            formulaIdentifier: $formulaIdentifier,
            operation: $operation,
            operands: $operands,
            inputs: array_keys($inputs),
            dependencyResults: $dependencyResults,
            excludedValues: [],
            resultValue: $resultValue,
            status: $status,
            reasonCode: $reasonCode,
            message: $message
        );
    }
}
