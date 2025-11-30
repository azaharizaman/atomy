<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\ValueObjects;

use Nexus\Monitoring\ValueObjects\MetricType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[Group('monitoring')]
#[Group('value-objects')]
final class MetricTypeTest extends TestCase
{
    #[Test]
    public function it_has_counter_type(): void
    {
        $this->assertSame('counter', MetricType::COUNTER->value);
    }

    #[Test]
    public function it_has_gauge_type(): void
    {
        $this->assertSame('gauge', MetricType::GAUGE->value);
    }

    #[Test]
    public function it_has_timing_type(): void
    {
        $this->assertSame('timing', MetricType::TIMING->value);
    }

    #[Test]
    public function it_has_histogram_type(): void
    {
        $this->assertSame('histogram', MetricType::HISTOGRAM->value);
    }

    #[Test]
    #[DataProvider('numericTypeProvider')]
    public function it_identifies_numeric_types(MetricType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isNumeric());
    }

    #[Test]
    #[DataProvider('typeLabelsProvider')]
    public function it_returns_correct_label(MetricType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->label());
    }

    public static function numericTypeProvider(): array
    {
        return [
            'Counter is numeric' => [MetricType::COUNTER, true],
            'Gauge is numeric' => [MetricType::GAUGE, true],
            'Timing is numeric' => [MetricType::TIMING, true],
            'Histogram is numeric' => [MetricType::HISTOGRAM, true],
        ];
    }

    public static function typeLabelsProvider(): array
    {
        return [
            'Counter label' => [MetricType::COUNTER, 'Counter'],
            'Gauge label' => [MetricType::GAUGE, 'Gauge'],
            'Timing label' => [MetricType::TIMING, 'Timing'],
            'Histogram label' => [MetricType::HISTOGRAM, 'Histogram'],
        ];
    }
}
