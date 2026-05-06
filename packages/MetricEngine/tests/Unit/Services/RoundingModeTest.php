<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class RoundingModeTest extends TestCase
{
    private NumericValueService $service;

    protected function setUp(): void
    {
        $this->service = new NumericValueService();
    }

    public function test_half_up_rounds_midpoint_away_from_zero(): void
    {
        $this->assertSame(2.5, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_UP)));
        $this->assertSame(-2.5, $this->service->round(-2.45, new PrecisionPolicy(1, RoundingMode::HALF_UP)));
    }

    public function test_half_down_rounds_midpoint_toward_zero(): void
    {
        $this->assertSame(2.4, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_DOWN)));
        $this->assertSame(-2.4, $this->service->round(-2.45, new PrecisionPolicy(1, RoundingMode::HALF_DOWN)));
    }

    public function test_half_even_rounds_midpoint_to_even_digit(): void
    {
        $this->assertSame(2.4, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_EVEN)));
        $this->assertSame(2.6, $this->service->round(2.55, new PrecisionPolicy(1, RoundingMode::HALF_EVEN)));
    }

    public function test_half_odd_rounds_midpoint_to_odd_digit(): void
    {
        $this->assertSame(2.5, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_ODD)));
        $this->assertSame(2.5, $this->service->round(2.55, new PrecisionPolicy(1, RoundingMode::HALF_ODD)));
    }

    public function test_toward_zero_truncates_at_scale(): void
    {
        $this->assertSame(2.4, $this->service->round(2.49, new PrecisionPolicy(1, RoundingMode::TOWARD_ZERO)));
        $this->assertSame(-2.4, $this->service->round(-2.49, new PrecisionPolicy(1, RoundingMode::TOWARD_ZERO)));
    }

    public function test_away_from_zero_rounds_any_fraction_away_from_zero(): void
    {
        $this->assertSame(2.5, $this->service->round(2.41, new PrecisionPolicy(1, RoundingMode::AWAY_FROM_ZERO)));
        $this->assertSame(-2.5, $this->service->round(-2.41, new PrecisionPolicy(1, RoundingMode::AWAY_FROM_ZERO)));
    }
}
