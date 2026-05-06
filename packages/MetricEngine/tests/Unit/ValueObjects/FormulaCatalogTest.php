<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Exceptions\DuplicateFormulaException;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class FormulaCatalogTest extends TestCase
{
    public function test_catalog_exposes_formulas_by_id_and_order(): void
    {
        $first = new FormulaDefinition('metric.one', AggregationType::SUM, [1], PrecisionPolicy::default());
        $second = new FormulaDefinition('metric.two', AggregationType::SUM, [2], PrecisionPolicy::default());

        $catalog = new FormulaCatalog([$first, $second]);

        $this->assertSame($first, $catalog->get('metric.one'));
        $this->assertSame(['metric.one', 'metric.two'], array_keys($catalog->all()));
    }

    public function test_catalog_rejects_duplicate_ids(): void
    {
        $this->expectException(DuplicateFormulaException::class);
        $this->expectExceptionMessage('Formula [metric.one] is already registered.');

        new FormulaCatalog([
            new FormulaDefinition('metric.one', AggregationType::SUM, [1], PrecisionPolicy::default()),
            new FormulaDefinition('metric.one', AggregationType::SUM, [2], PrecisionPolicy::default()),
        ]);
    }

    public function test_get_missing_formula_fails_loudly(): void
    {
        $this->expectException(MissingInputException::class);
        $this->expectExceptionMessage('Required metric input [metric.missing] is missing.');

        (new FormulaCatalog([]))->get('metric.missing');
    }

    public function test_has_returns_true_for_existing_formula(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.one', AggregationType::SUM, [1], PrecisionPolicy::default()),
        ]);

        $this->assertTrue($catalog->has('metric.one'));
        $this->assertFalse($catalog->has('metric.missing'));
    }

    public function test_all_returns_identifier_mapped_to_formula(): void
    {
        $formula = new FormulaDefinition('metric.one', AggregationType::SUM, [1], PrecisionPolicy::default());

        $catalog = new FormulaCatalog([$formula]);
        $all = $catalog->all();

        $this->assertCount(1, $all);
        $this->assertSame($formula, $all['metric.one']);
    }
}
