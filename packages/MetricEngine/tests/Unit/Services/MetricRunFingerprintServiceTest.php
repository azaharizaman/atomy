<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use Nexus\MetricEngine\Services\FormulaGraphService;
use Nexus\MetricEngine\Services\MetricRunFingerprintService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class MetricRunFingerprintServiceTest extends TestCase
{
    public function test_fingerprint_is_stable_for_equivalent_inputs(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService(), new FormulaGraphService());
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $first = $service->fingerprint($catalog, [
            'a' => new MetricInput('a', 10),
            'b' => new MetricInput('b', 5),
        ]);

        $second = $service->fingerprint($catalog, [
            'b' => new MetricInput('b', 5),
            'a' => new MetricInput('a', 10),
        ]);

        $this->assertSame('sha256', $first->algorithm);
        $this->assertSame($first->hash, $second->hash);
    }

    public function test_fingerprint_changes_when_input_changes(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService(), new FormulaGraphService());
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $first = $service->fingerprint($catalog, ['a' => new MetricInput('a', 10)]);
        $second = $service->fingerprint($catalog, ['a' => new MetricInput('a', 11)]);

        $this->assertNotSame($first->hash, $second->hash);
    }

    public function test_fingerprint_is_stable_for_equivalent_catalogs_with_different_insertion_order(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService(), new FormulaGraphService());

        $firstCatalog = new FormulaCatalog([
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
            new FormulaDefinition(
                'metric.ratio',
                AggregationType::RATIO,
                [new FormulaReference('metric.delta'), 'revenue'],
                PrecisionPolicy::default()
            ),
        ]);

        $secondCatalog = new FormulaCatalog([
            new FormulaDefinition(
                'metric.ratio',
                AggregationType::RATIO,
                [new FormulaReference('metric.delta'), 'revenue'],
                PrecisionPolicy::default()
            ),
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
        ]);

        $first = $service->fingerprint($firstCatalog, [
            'revenue' => new MetricInput('revenue', 100),
            'cost' => new MetricInput('cost', 60),
        ]);

        $second = $service->fingerprint($secondCatalog, [
            'cost' => new MetricInput('cost', 60),
            'revenue' => new MetricInput('revenue', 100),
        ]);

        $this->assertSame($first->hash, $second->hash);
    }

    public function test_fingerprint_is_stable_for_equivalent_formula_metadata_with_different_key_order(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService(), new FormulaGraphService());

        $firstCatalog = new FormulaCatalog([
            new FormulaDefinition(
                'metric.total',
                AggregationType::SUM,
                ['a', 'b'],
                PrecisionPolicy::default(),
                metadata: [
                    'owner' => [
                        'team' => 'finance',
                        'source' => 'rfq',
                    ],
                    'flags' => ['approved', 'audited'],
                ]
            ),
        ]);

        $secondCatalog = new FormulaCatalog([
            new FormulaDefinition(
                'metric.total',
                AggregationType::SUM,
                ['a', 'b'],
                PrecisionPolicy::default(),
                metadata: [
                    'flags' => ['approved', 'audited'],
                    'owner' => [
                        'source' => 'rfq',
                        'team' => 'finance',
                    ],
                ]
            ),
        ]);

        $first = $service->fingerprint($firstCatalog, [
            'a' => new MetricInput('a', 10),
            'b' => new MetricInput('b', 5),
        ]);

        $second = $service->fingerprint($secondCatalog, [
            'a' => new MetricInput('a', 10),
            'b' => new MetricInput('b', 5),
        ]);

        $this->assertSame($first->hash, $second->hash);
    }
}
