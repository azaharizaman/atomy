<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Services\FormulaCatalogBuilderService;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use PHPUnit\Framework\TestCase;

class FormulaCatalogBuilderServiceTest extends TestCase
{
    public function test_builds_catalog_from_serialized_formulas(): void
    {
        $builder = new FormulaCatalogBuilderService(new FormulaDefinitionSerializerService());

        $catalog = $builder->fromArrays([
            [
                'identifier' => 'metric.one',
                'operation' => 'sum',
                'operands' => [1, 2],
                'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
            ],
        ]);

        $this->assertSame('metric.one', $catalog->get('metric.one')->identifier());
    }
}
