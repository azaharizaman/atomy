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
                    $options->includeAuditTrace ? new MetricAuditTrace(
                        formulaIdentifier: $formulaIdentifier,
                        operation: $catalog->get($formulaIdentifier)->operation()->value,
                        operands: $catalog->get($formulaIdentifier)->operands(),
                        inputs: array_keys($inputs),
                        dependencyResults: [],
                        excludedValues: [],
                        resultValue: null,
                        status: MetricResultStatus::NOT_AVAILABLE->value,
                        reasonCode: 'dependency_not_available',
                        message: "Formula [{$formulaIdentifier}] depends on unavailable formula [{$unavailableDependency}]."
                    ) : null
                );
                continue;
            }

            $formula = $catalog->get($formulaIdentifier);

            try {
                $result = $this->formulaEvaluator->evaluate($formula, $runtimeInputs);
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::available(
                    $result,
                    $options->includeAuditTrace ? new MetricAuditTrace(
                        formulaIdentifier: $formulaIdentifier,
                        operation: $formula->operation()->value,
                        operands: $formula->operands(),
                        inputs: array_keys($inputs),
                        dependencyResults: $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes),
                        excludedValues: [],
                        resultValue: $result->value(),
                        status: MetricResultStatus::AVAILABLE->value,
                        reasonCode: null,
                        message: null
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
                    $options->includeAuditTrace ? new MetricAuditTrace(
                        formulaIdentifier: $formulaIdentifier,
                        operation: $formula->operation()->value,
                        operands: $formula->operands(),
                        inputs: array_keys($inputs),
                        dependencyResults: $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes),
                        excludedValues: [],
                        resultValue: null,
                        status: $status->value,
                        reasonCode: $error instanceof \Nexus\MetricEngine\Exceptions\MetricEngineException ? $error->errorCode() : 'unexpected_error',
                        message: $error->getMessage()
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
}
