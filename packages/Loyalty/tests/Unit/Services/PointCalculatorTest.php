<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use InvalidArgumentException;
use Nexus\Common\ValueObjects\Money;
use Nexus\Loyalty\Exceptions\AccrualCapExceededException;
use Nexus\Loyalty\Enums\RoundingStrategy;
use Nexus\Loyalty\Services\PointCalculationEngine;
use Nexus\Loyalty\Contracts\LoyaltySettingsInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class PointCalculatorTest extends TestCase
{
    private LoyaltySettingsInterface|MockObject $settings;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(LoyaltySettingsInterface::class);
        $this->settings->method('getBaseRate')->willReturn(1.0);
        $this->settings->method('getExperientialRewards')->willReturn([
            'social_share' => 50,
            'eco_action' => 100,
            'on_time_payment' => 200,
        ]);
    }

    public function test_it_calculates_points_with_multipliers(): void
    {
        $engine = new PointCalculationEngine($this->settings, RoundingStrategy::Floor);
        $amount = new Money(1000, 'USD'); // $10.00

        // 10 * 1.0 * 1.5 = 15
        $points = $engine->calculateAccrual($amount, 'USD', ['tier' => 1.5]);

        $this->assertEquals(15, $points);
    }

    public function test_it_validates_currency_consistency(): void
    {
        $engine = new PointCalculationEngine($this->settings);
        $amount = new Money(1000, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');
        $engine->calculateAccrual($amount, 'EUR');
    }

    public function test_rounding_strategies(): void
    {
        $amount = new Money(1000, 'USD'); // $10.00
        $multipliers = ['promo' => 1.55]; // 10 * 1.55 = 15.5

        $floorEngine = new PointCalculationEngine($this->settings, RoundingStrategy::Floor);
        $this->assertEquals(15, $floorEngine->calculateAccrual($amount, 'USD', $multipliers));

        $ceilEngine = new PointCalculationEngine($this->settings, RoundingStrategy::Ceil);
        $this->assertEquals(16, $ceilEngine->calculateAccrual($amount, 'USD', $multipliers));

        $nearestEngine = new PointCalculationEngine($this->settings, RoundingStrategy::Nearest);
        $this->assertEquals(16, $nearestEngine->calculateAccrual($amount, 'USD', $multipliers));
    }

    public function test_multi_factor_accrual(): void
    {
        $amount = new Money(1000, 'USD'); // $10.00
        $multipliers = ['m1' => 1.5, 'm2' => 2.0];

        // Multiplicative: 10 * 1.5 * 2.0 = 30
        $multiEngine = new PointCalculationEngine($this->settings, RoundingStrategy::Floor, null, true);
        $this->assertEquals(30, $multiEngine->calculateAccrual($amount, 'USD', $multipliers));

        // Additive: 10 * (1.0 + (0.5 + 1.0)) = 10 * 2.5 = 25
        $additiveEngine = new PointCalculationEngine($this->settings, RoundingStrategy::Floor, null, false);
        $this->assertEquals(25, $additiveEngine->calculateAccrual($amount, 'USD', $multipliers));
    }

    public function test_accrual_safety_caps(): void
    {
        $engine = new PointCalculationEngine($this->settings, RoundingStrategy::Floor, 100);
        $amount = new Money(20000, 'USD'); // $200.00

        try {
            $engine->calculateAccrual($amount, 'USD');
            $this->fail('AccrualCapExceededException was not thrown');
        } catch (AccrualCapExceededException $e) {
            $this->assertStringContainsString('exceeds transaction cap of 100', $e->getMessage());
        }
    }

    public function test_refund_clawback_logic(): void
    {
        $engine = new PointCalculationEngine($this->settings);
        $this->assertEquals(50, $engine->calculateClawback(100, 0.5));
        $this->assertEquals(100, $engine->calculateClawback(100, 1.0));
    }

    public function test_it_calculates_experiential_rewards(): void
    {
        $engine = new PointCalculationEngine($this->settings);
        $this->assertEquals(50, $engine->calculateExperientialReward('social_share'));
        $this->assertEquals(200, $engine->calculateExperientialReward('on_time_payment'));
        
        // Unknown event type returns 0
        $this->assertEquals(0, $engine->calculateExperientialReward('unknown_event'));

        // Override via context
        $this->assertEquals(500, $engine->calculateExperientialReward('social_share', ['points_override' => 500]));
    }
}
