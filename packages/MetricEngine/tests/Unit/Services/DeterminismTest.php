<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Services\FormulaEvaluatorService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class DeterminismTest extends TestCase
{
    public function test_identical_inputs_produce_identical_results_without_runtime_timestamp(): void
    {
        $numeric = new NumericValueService();
        $evaluator = new FormulaEvaluatorService(
            new ScalarMetricCalculatorService($numeric),
            new \Nexus\MetricEngine\Services\TimeSeriesMetricCalculatorService(
                $numeric,
                new \Nexus\MetricEngine\Services\WindowResolverService(new \Nexus\MetricEngine\Services\PeriodComparatorService()),
                new \Nexus\MetricEngine\Services\ComparisonService($numeric)
            )
        );
        $formula = new FormulaDefinition('metric.ratio', AggregationType::RATIO, ['actual', 'target'], PrecisionPolicy::default());
        $inputs = [
            'actual' => new MetricInput('actual', 75),
            'target' => new MetricInput('target', 100),
        ];

        $first = $evaluator->evaluate($formula, $inputs);
        $second = $evaluator->evaluate($formula, $inputs);

        $this->assertEquals($first, $second);
        $this->assertObjectNotHasProperty('evaluationTimestamp', $first);
    }
}
