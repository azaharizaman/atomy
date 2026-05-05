<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\Exceptions\TypeMismatchException;
use Nexus\MetricEngine\Services\FormulaEvaluatorService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use PHPUnit\Framework\TestCase;

class FormulaEvaluatorServiceTest extends TestCase
{
    private FormulaEvaluatorService $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new FormulaEvaluatorService(
            new ScalarMetricCalculatorService(new NumericValueService())
        );
    }

    public function test_evaluates_simple_ratio_formula(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.ratio',
            operation: AggregationType::RATIO,
            operands: ['actual', 'target'],
            precisionPolicy: new PrecisionPolicy(2)
        );

        $inputs = [
            'actual' => new MetricInput('actual', 75),
            'target' => new MetricInput('target', 100),
        ];

        $result = $this->evaluator->evaluate($formula, $inputs);

        $this->assertSame(0.75, $result->value());
        $this->assertSame('metric.ratio', $result->formulaIdentifier());
        $this->assertSame(InputMode::SCALAR, $result->inputMode());
    }

    public function test_evaluates_nested_scalar_formula(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.margin_ratio',
            operation: AggregationType::RATIO,
            operands: [
                new FormulaDefinition('metric.margin_delta', AggregationType::DELTA, ['revenue', 'cogs'], new PrecisionPolicy(4)),
                'revenue',
            ],
            precisionPolicy: new PrecisionPolicy(4)
        );

        $result = $this->evaluator->evaluate($formula, [
            'revenue' => new MetricInput('revenue', 1000),
            'cogs' => new MetricInput('cogs', 600),
        ]);

        $this->assertSame(0.4, $result->value());
        $this->assertSame('metric.margin_ratio', $result->formulaIdentifier());
        $this->assertSame(InputMode::SCALAR, $result->inputMode());
    }

    public function test_evaluates_formula_with_constants(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.half_revenue',
            operation: AggregationType::RATIO,
            operands: ['revenue', 2],
            precisionPolicy: new PrecisionPolicy(2)
        );

        $result = $this->evaluator->evaluate($formula, [
            'revenue' => new MetricInput('revenue', 100),
        ]);

        $this->assertSame(50.0, $result->value());
    }

    public function test_missing_named_input_fails_loudly(): void
    {
        $this->expectException(MissingInputException::class);
        $this->expectExceptionMessage('Required metric input [revenue] is missing.');

        $this->evaluator->evaluate(
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, ['revenue', 100], PrecisionPolicy::default()),
            []
        );
    }

    public function test_evaluates_sum_formula(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.total',
            operation: AggregationType::SUM,
            operands: [1, 2, 3, 4, 5],
            precisionPolicy: PrecisionPolicy::default()
        );

        $result = $this->evaluator->evaluate($formula, []);

        $this->assertSame(15.0, $result->value());
    }

    public function test_evaluates_weighted_score_formula(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.weighted',
            operation: AggregationType::WEIGHTED_SCORE,
            operands: [
                [80, 90, 70],
                [0.5, 0.3, 0.2],
            ],
            precisionPolicy: PrecisionPolicy::default()
        );

        $result = $this->evaluator->evaluate($formula, []);

        $this->assertSame(81.0, $result->value());
    }
}
