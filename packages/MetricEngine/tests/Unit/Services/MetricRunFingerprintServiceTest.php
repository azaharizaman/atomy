<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use Nexus\MetricEngine\Services\MetricRunFingerprintService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class MetricRunFingerprintServiceTest extends TestCase
{
    public function test_fingerprint_is_stable_for_equivalent_inputs(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService());
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
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService());
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $first = $service->fingerprint($catalog, ['a' => new MetricInput('a', 10)]);
        $second = $service->fingerprint($catalog, ['a' => new MetricInput('a', 11)]);

        $this->assertNotSame($first->hash, $second->hash);
    }
}
