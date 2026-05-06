<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Services\BatchFormulaEvaluatorService;
use Nexus\MetricEngine\Services\FormulaEvaluatorService;
use Nexus\MetricEngine\Services\FormulaGraphService;
use Nexus\MetricEngine\Services\MetricStatusInferenceService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class BatchFormulaEvaluatorServiceTest extends TestCase
{
    private BatchFormulaEvaluatorService $service;

    protected function setUp(): void
    {
        $numeric = new NumericValueService();

        $this->service = new BatchFormulaEvaluatorService(
            new FormulaEvaluatorService(new ScalarMetricCalculatorService($numeric)),
            new FormulaGraphService(),
            new MetricStatusInferenceService()
        );
    }

    public function test_evaluates_independent_formulas(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, [
            'a' => new MetricInput('a', 10),
            'b' => new MetricInput('b', 5),
        ]);

        $outcome = $result->get('metric.total');
        $this->assertSame(MetricResultStatus::AVAILABLE, $outcome->status);
        $this->assertSame(15.0, $outcome->result?->value());
    }

    public function test_evaluates_formula_dependencies(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, [
            'revenue' => new MetricInput('revenue', 100),
            'cost' => new MetricInput('cost', 60),
        ]);

        $this->assertSame(40.0, $result->get('metric.delta')->result?->value());
        $this->assertSame(0.4, $result->get('metric.ratio')->result?->value());
    }

    public function test_missing_input_becomes_not_available(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['missing'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, []);

        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $result->get('metric.total')->status);
        $this->assertNull($result->get('metric.total')->result);
    }

    public function test_dependency_failure_marks_dependent_not_available(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['missing', 'cost'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'cost'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, [
            'cost' => new MetricInput('cost', 60),
        ]);

        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $result->get('metric.delta')->status);
        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $result->get('metric.ratio')->status);
    }

    public function test_batch_evaluation_can_include_audit_trace(): void
    {
        $catalog = new \Nexus\MetricEngine\ValueObjects\FormulaCatalog([
            new \Nexus\MetricEngine\ValueObjects\FormulaDefinition(
                'metric.total',
                \Nexus\MetricEngine\Enums\AggregationType::SUM,
                ['a', 'b'],
                \Nexus\MetricEngine\ValueObjects\PrecisionPolicy::default()
            ),
        ]);

        $result = $this->service->evaluate($catalog, [
            'a' => new \Nexus\MetricEngine\ValueObjects\MetricInput('a', 10),
            'b' => new \Nexus\MetricEngine\ValueObjects\MetricInput('b', 5),
        ], \Nexus\MetricEngine\ValueObjects\MetricEvaluationOptions::withAuditTrace());

        $trace = $result->get('metric.total')->auditTrace;

        $this->assertNotNull($trace);
        $this->assertSame('metric.total', $trace->formulaIdentifier);
        $this->assertSame('sum', $trace->operation);
        $this->assertSame(['a', 'b'], $trace->operands);
        $this->assertSame(15.0, $trace->resultValue);
        $this->assertSame('available', $trace->status);
    }
}
