<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Exceptions\FormulaDependencyException;
use Nexus\MetricEngine\Services\FormulaGraphService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class FormulaGraphServiceTest extends TestCase
{
    private FormulaGraphService $service;

    protected function setUp(): void
    {
        $this->service = new FormulaGraphService();
    }

    public function test_orders_dependencies_before_dependents(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
        ]);

        $graph = $this->service->build($catalog);

        $this->assertSame(['metric.delta', 'metric.ratio'], $graph->orderedFormulaIds());
        $this->assertSame(['metric.delta'], $graph->dependenciesFor('metric.ratio'));
    }

    public function test_rejects_missing_formula_reference(): void
    {
        $this->expectException(FormulaDependencyException::class);
        $this->expectExceptionMessage('Formula [metric.ratio] references missing formula [metric.delta].');

        $this->service->build(new FormulaCatalog([
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
        ]));
    }

    public function test_rejects_circular_formula_reference(): void
    {
        $this->expectException(FormulaDependencyException::class);
        $this->expectExceptionMessage('Formula dependency cycle detected.');

        $this->service->build(new FormulaCatalog([
            new FormulaDefinition('metric.a', AggregationType::SUM, [new FormulaReference('metric.b')], PrecisionPolicy::default()),
            new FormulaDefinition('metric.b', AggregationType::SUM, [new FormulaReference('metric.a')], PrecisionPolicy::default()),
        ]));
    }

    public function test_formula_with_no_dependencies(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.simple', AggregationType::SUM, ['revenue'], PrecisionPolicy::default()),
        ]);

        $graph = $this->service->build($catalog);

        $this->assertSame(['metric.simple'], $graph->orderedFormulaIds());
        $this->assertSame([], $graph->dependenciesFor('metric.simple'));
    }

    public function test_empty_catalog(): void
    {
        $catalog = new FormulaCatalog([]);

        $graph = $this->service->build($catalog);

        $this->assertSame([], $graph->orderedFormulaIds());
    }

    public function test_three_plus_formula_chain(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.c', AggregationType::SUM, [new FormulaReference('metric.b')], PrecisionPolicy::default()),
            new FormulaDefinition('metric.a', AggregationType::SUM, ['revenue'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.b', AggregationType::SUM, [new FormulaReference('metric.a')], PrecisionPolicy::default()),
        ]);

        $graph = $this->service->build($catalog);

        $this->assertSame(['metric.a', 'metric.b', 'metric.c'], $graph->orderedFormulaIds());
        $this->assertSame(['metric.a'], $graph->dependenciesFor('metric.b'));
        $this->assertSame(['metric.b'], $graph->dependenciesFor('metric.c'));
    }
}
