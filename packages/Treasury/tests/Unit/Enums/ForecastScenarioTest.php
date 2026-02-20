<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Enums;

use Nexus\Treasury\Enums\ForecastScenario;
use PHPUnit\Framework\TestCase;

final class ForecastScenarioTest extends TestCase
{
    public function test_label_returns_correct_string(): void
    {
        $this->assertEquals('Optimistic', ForecastScenario::OPTIMISTIC->label());
        $this->assertEquals('Base', ForecastScenario::BASE->label());
        $this->assertEquals('Pessimistic', ForecastScenario::PESSIMISTIC->label());
    }

    public function test_is_optimistic_returns_true_for_optimistic(): void
    {
        $this->assertTrue(ForecastScenario::OPTIMISTIC->isOptimistic());
        $this->assertFalse(ForecastScenario::BASE->isOptimistic());
    }

    public function test_is_base_returns_true_for_base(): void
    {
        $this->assertTrue(ForecastScenario::BASE->isBase());
        $this->assertFalse(ForecastScenario::OPTIMISTIC->isBase());
    }

    public function test_is_pessimistic_returns_true_for_pessimistic(): void
    {
        $this->assertTrue(ForecastScenario::PESSIMISTIC->isPessimistic());
        $this->assertFalse(ForecastScenario::BASE->isPessimistic());
    }

    public function test_risk_factor_returns_correct_value(): void
    {
        $this->assertEquals(0.8, ForecastScenario::OPTIMISTIC->riskFactor());
        $this->assertEquals(1.0, ForecastScenario::BASE->riskFactor());
        $this->assertEquals(1.2, ForecastScenario::PESSIMISTIC->riskFactor());
    }

    public function test_adjustment_percentage_returns_correct_value(): void
    {
        $this->assertEquals(0.1, ForecastScenario::OPTIMISTIC->adjustmentPercentage());
        $this->assertEquals(0.0, ForecastScenario::BASE->adjustmentPercentage());
        $this->assertEquals(-0.15, ForecastScenario::PESSIMISTIC->adjustmentPercentage());
    }
}
