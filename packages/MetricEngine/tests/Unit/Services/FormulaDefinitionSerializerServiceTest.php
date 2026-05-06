<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\ComparisonType;
use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Exceptions\FormulaSerializationException;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class FormulaDefinitionSerializerServiceTest extends TestCase
{
    private FormulaDefinitionSerializerService $serializer;

    protected function setUp(): void
    {
        $this->serializer = new FormulaDefinitionSerializerService();
    }

    public function test_round_trips_formula_with_reference_window_comparison_unit_and_metadata(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.margin_ratio',
            operation: AggregationType::RATIO,
            operands: [
                new FormulaReference('metric.margin_delta'),
                'revenue',
            ],
            precisionPolicy: new PrecisionPolicy(4, RoundingMode::HALF_EVEN),
            window: TimeWindow::explicitRange('2026-01', '2026-03'),
            comparison: new ComparisonDefinition(ComparisonType::PREVIOUS_PERIOD),
            unit: 'ratio',
            metadata: ['display_group' => 'finance']
        );

        $array = $this->serializer->toArray($formula);
        $roundTripped = $this->serializer->fromArray($array);

        $this->assertSame('metric.margin_ratio', $roundTripped->identifier());
        $this->assertSame(AggregationType::RATIO, $roundTripped->operation());
        $this->assertSame('ratio', $roundTripped->unit());
        $this->assertSame(['display_group' => 'finance'], $roundTripped->metadata());
        $this->assertInstanceOf(FormulaReference::class, $roundTripped->operands()[0]);
        $this->assertSame('metric.margin_delta', $roundTripped->operands()[0]->identifier);
        $this->assertSame(RoundingMode::HALF_EVEN, $roundTripped->precisionPolicy()->roundingMode);
    }

    public function test_rejects_missing_identifier(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage('Serialized formula requires identifier.');

        $this->serializer->fromArray([
            'operation' => 'sum',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
        ]);
    }

    public function test_rejects_invalid_operation(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage('Unsupported formula operation [unknown].');

        $this->serializer->fromArray([
            'identifier' => 'metric.bad',
            'operation' => 'unknown',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
        ]);
    }
}
