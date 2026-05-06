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

    public function test_round_trips_nested_formula_operands_as_config_arrays(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.weighted_total',
            operation: AggregationType::SUM,
            operands: [
                new FormulaDefinition(
                    identifier: 'metric.adjustment',
                    operation: AggregationType::DELTA,
                    operands: ['gross', 'discount'],
                    precisionPolicy: new PrecisionPolicy(2, RoundingMode::HALF_DOWN),
                    unit: 'amount',
                    metadata: ['source' => 'nested']
                ),
                'tax',
            ],
            precisionPolicy: PrecisionPolicy::default(),
        );

        $array = $this->serializer->toArray($formula);

        $this->assertIsArray($array['operands'][0]);
        $this->assertArrayHasKey('identifier', $array['operands'][0]);
        $this->assertArrayHasKey('operation', $array['operands'][0]);
        foreach ($array['operands'] as $operand) {
            $this->assertNotInstanceOf(FormulaDefinition::class, $operand);
        }

        $roundTripped = $this->serializer->fromArray($array);

        $this->assertInstanceOf(FormulaDefinition::class, $roundTripped->operands()[0]);
        $this->assertSame('metric.adjustment', $roundTripped->operands()[0]->identifier());
        $this->assertSame(RoundingMode::HALF_DOWN, $roundTripped->operands()[0]->precisionPolicy()->roundingMode);
        $this->assertSame(['source' => 'nested'], $roundTripped->operands()[0]->metadata());
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

    public function test_rejects_invalid_rounding_mode(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage("Unsupported rounding mode [invalid].");

        $this->serializer->fromArray([
            'identifier' => 'metric.test',
            'operation' => 'sum',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'invalid'],
        ]);
    }

    public function test_rejects_invalid_window_type(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage("Unsupported window type [invalid].");

        $this->serializer->fromArray([
            'identifier' => 'metric.test',
            'operation' => 'sum',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
            'window' => ['type' => 'invalid'],
        ]);
    }

    public function test_rejects_invalid_comparison_type(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage("Unsupported comparison type [invalid].");

        $this->serializer->fromArray([
            'identifier' => 'metric.test',
            'operation' => 'sum',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
            'comparison' => ['type' => 'invalid'],
        ]);
    }
}
