<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\ComparisonType;
use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Enums\ValueType;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\Exceptions\TypeMismatchException;
use Nexus\MetricEngine\Services\FormulaEvaluatorService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\Services\TimeSeriesMetricCalculatorService;
use Nexus\MetricEngine\Services\PeriodComparatorService;
use Nexus\MetricEngine\Services\WindowResolverService;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class FormulaEvaluatorServiceTest extends TestCase
{
    private FormulaEvaluatorService $evaluator;

    protected function setUp(): void
    {
        $numericService = new NumericValueService();

        $this->evaluator = new FormulaEvaluatorService(
            new ScalarMetricCalculatorService($numericService),
            new TimeSeriesMetricCalculatorService(
                $numericService,
                new WindowResolverService(new PeriodComparatorService()),
                new \Nexus\MetricEngine\Services\ComparisonService($numericService)
            )
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

    public function test_evaluates_weighted_score_formula_with_named_input_arrays(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.weighted',
            operation: AggregationType::WEIGHTED_SCORE,
            operands: [
                ['quality', 'delivery', 'price'],
                [0.5, 0.3, 0.2],
            ],
            precisionPolicy: PrecisionPolicy::default()
        );

        $result = $this->evaluator->evaluate($formula, [
            'quality' => new MetricInput('quality', 80),
            'delivery' => new MetricInput('delivery', 90),
            'price' => new MetricInput('price', 70),
        ]);

        $this->assertSame(81.0, $result->value());
    }

    public function test_evaluates_rolling_sum_formula_against_series_input(): void
    {
        $window = TimeWindow::fixedRolling(2);
        $formula = new FormulaDefinition(
            identifier: 'metric.rolling_sales',
            operation: AggregationType::ROLLING_SUM,
            operands: ['sales'],
            precisionPolicy: PrecisionPolicy::default(),
            window: $window
        );

        $result = $this->evaluator->evaluate($formula, [
            'sales' => new MetricSeries('sales', [
                new TimeSeriesPoint('2026-01', 10),
                new TimeSeriesPoint('2026-02', 20),
                new TimeSeriesPoint('2026-03', 30),
            ], 'units'),
        ]);

        $this->assertSame(50.0, $result->value());
        $this->assertSame(ValueType::NUMBER, $result->valueType());
        $this->assertSame(InputMode::TIME_SERIES, $result->inputMode());
        $this->assertSame('units', $result->unit());
        $this->assertSame($window, $result->window());
    }

    public function test_evaluates_previous_period_comparison_formula(): void
    {
        $comparison = new ComparisonDefinition(ComparisonType::PREVIOUS_PERIOD);
        $formula = new FormulaDefinition(
            identifier: 'metric.period_compare',
            operation: AggregationType::PERIOD_COMPARE,
            operands: ['current', 'previous'],
            precisionPolicy: new PrecisionPolicy(2),
            comparison: $comparison
        );

        $result = $this->evaluator->evaluate($formula, [
            'current' => new MetricInput('current', 120),
            'previous' => new MetricInput('previous', 100),
        ]);

        $this->assertSame(['delta' => 20.0, 'percentChange' => 0.2], $result->value());
        $this->assertSame(ValueType::COMPARISON, $result->valueType());
        $this->assertSame($comparison, $result->comparison());
    }

    public function test_binary_formula_rejects_missing_operand_with_package_exception(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Operation [ratio] requires exactly 2 operands.');

        $this->evaluator->evaluate(
            new FormulaDefinition('metric.bad_ratio', AggregationType::RATIO, [10], PrecisionPolicy::default()),
            []
        );
    }

    public function test_rolling_formula_requires_time_window(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Operation [rolling_sum] requires a time window.');

        $this->evaluator->evaluate(
            new FormulaDefinition('metric.bad_rolling', AggregationType::ROLLING_SUM, ['sales'], PrecisionPolicy::default()),
            [
                'sales' => new MetricSeries('sales', [
                    new TimeSeriesPoint('2026-01', 10),
                    new TimeSeriesPoint('2026-02', 20),
                ]),
            ]
        );
    }

    public function test_scalar_formula_rejects_series_input(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('Scalar operation [sum] received a time-series input.');

        $this->evaluator->evaluate(
            new FormulaDefinition('metric.bad_sum', AggregationType::SUM, ['sales'], PrecisionPolicy::default()),
            [
                'sales' => new MetricSeries('sales', [
                    new TimeSeriesPoint('2026-01', 10),
                    new TimeSeriesPoint('2026-02', 20),
                ]),
            ]
        );
    }

    public function test_period_compare_requires_previous_period_definition(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Operation [period_compare] requires a previous-period comparison definition.');

        $this->evaluator->evaluate(
            new FormulaDefinition('metric.bad_compare', AggregationType::PERIOD_COMPARE, ['current', 'previous'], PrecisionPolicy::default()),
            [
                'current' => new MetricInput('current', 120),
                'previous' => new MetricInput('previous', 100),
            ]
        );
    }

    public function test_n_ary_scalar_formula_rejects_nested_array_operand(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('Operation [sum] requires scalar numeric operands.');

        $this->evaluator->evaluate(
            new FormulaDefinition('metric.bad_sum', AggregationType::SUM, [[1, 2], 3], PrecisionPolicy::default()),
            []
        );
    }

    public function test_weighted_formula_rejects_non_numeric_nested_operand(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('Operation [weighted_score] requires numeric value and weight arrays.');

        $this->evaluator->evaluate(
            new FormulaDefinition(
                'metric.bad_weighted',
                AggregationType::WEIGHTED_SCORE,
                [[80, new \stdClass()], [0.5, 0.5]],
                PrecisionPolicy::default()
            ),
            []
        );
    }

    public function test_period_compare_allows_negative_previous_period_value(): void
    {
        $result = $this->evaluator->evaluate(
            new FormulaDefinition(
                identifier: 'metric.negative_compare',
                operation: AggregationType::PERIOD_COMPARE,
                operands: ['current', 'previous'],
                precisionPolicy: new PrecisionPolicy(2),
                comparison: new ComparisonDefinition(ComparisonType::PREVIOUS_PERIOD)
            ),
            [
                'current' => new MetricInput('current', -50),
                'previous' => new MetricInput('previous', -100),
            ]
        );

        $this->assertSame(['delta' => 50.0, 'percentChange' => -0.5], $result->value());
    }
}
