<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationBatchResult;
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
    public function evaluate(FormulaCatalog $catalog, array $inputs): MetricEvaluationBatchResult
    {
        $graph = $this->graphService->build($catalog);
        $outcomes = [];
        $runtimeInputs = $inputs;

        foreach ($graph->orderedFormulaIds() as $formulaIdentifier) {
            $unavailableDependency = $this->firstUnavailableDependency($graph->dependenciesFor($formulaIdentifier), $outcomes);

            if ($unavailableDependency !== null) {
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::dependencyUnavailable($formulaIdentifier, $unavailableDependency);
                continue;
            }

            $formula = $catalog->get($formulaIdentifier);

            try {
                $result = $this->formulaEvaluator->evaluate($formula, $runtimeInputs);
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::available($result);

                if (! is_int($result->value()) && ! is_float($result->value()) && ! is_string($result->value())) {
                    continue;
                }

                $runtimeInputs[$formulaIdentifier] = new MetricInput($formulaIdentifier, $result->value(), $result->unit());
            } catch (\Throwable $error) {
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::unavailable(
                    $formulaIdentifier,
                    $this->statusInference->infer($error),
                    $error
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
}
