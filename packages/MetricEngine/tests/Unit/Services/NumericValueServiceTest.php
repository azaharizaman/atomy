<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\InvalidNumericValueException;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\Services\NumericValueService;
use PHPUnit\Framework\TestCase;

class NumericValueServiceTest extends TestCase
{
    private NumericValueService $service;

    protected function setUp(): void
    {
        $this->service = new NumericValueService();
    }

    public function test_normalizes_integer_to_float(): void
    {
        $this->assertSame(10.0, $this->service->normalize(10));
    }

    public function test_normalizes_float_to_float(): void
    {
        $this->assertSame(12.5, $this->service->normalize(12.5));
    }

    public function test_normalizes_numeric_string_to_float(): void
    {
        $this->assertSame(42.0, $this->service->normalize('42'));
        $this->assertSame(3.14, $this->service->normalize('3.14'));
    }

    public function test_rejects_non_numeric_string(): void
    {
        $this->expectException(InvalidNumericValueException::class);
        $this->expectExceptionMessage('Metric value [abc] is not numeric.');

        $this->service->normalize('abc');
    }

    public function test_rejects_infinite_value(): void
    {
        $this->expectException(InvalidNumericValueException::class);
        $this->expectExceptionMessage('Metric value must be finite.');

        $this->service->normalize(INF);
    }

    public function test_rejects_nan_value(): void
    {
        $this->expectException(InvalidNumericValueException::class);

        $this->service->normalize(NAN);
    }

    public function test_rounds_half_up_to_requested_scale(): void
    {
        $this->assertSame(12.35, $this->service->round(12.345, new PrecisionPolicy(2)));
    }

    public function test_rounds_to_zero_scale(): void
    {
        $this->assertSame(12.0, $this->service->round(12.345, new PrecisionPolicy(0)));
    }

    public function test_rounds_half_up_at_boundary(): void
    {
        $this->assertSame(2.5, $this->service->round(2.45, new PrecisionPolicy(1)));
        $this->assertSame(2.6, $this->service->round(2.55, new PrecisionPolicy(1)));
    }
}
