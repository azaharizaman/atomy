<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\Declining150DepreciationMethod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for Declining150DepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class Declining150DepreciationMethodTest extends TestCase
{
    private readonly Declining150DepreciationMethod $method;

    protected function setUp(): void
    {
        $this->method = new Declining150DepreciationMethod(
            switchToStraightLine: true
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithDefaultValues(): void
    {
        $defaultMethod = new Declining150DepreciationMethod();
        
        $this->assertTrue($defaultMethod->isSwitchToStraightLineEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCustomValues(): void
    {
        $this->assertTrue($this->method->isSwitchToStraightLineEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidInputs_returnsDepreciationAmount(): void
    {
        $result = $this->method->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 12,
                'currency' => 'USD',
            ]
        );

        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withAccumulatedDepreciation_usesRemainingValue(): void
    {
        $result = $this->method->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 5000.00,
                'remaining_months' => 6,
                'currency' => 'USD',
            ]
        );

        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withZeroUsefulLife_returnsZero(): void
    {
        $result = $this->method->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 0,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 0,
                'currency' => 'USD',
            ]
        );

        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withFullAccumulatedDepreciation_returnsZero(): void
    {
        $result = $this->method->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 9000.00,
                'remaining_months' => 0,
                'currency' => 'USD',
            ]
        );

        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withNoSwitchToStraightLine_usesDecliningBalanceOnly(): void
    {
        $noSwitchMethod = new Declining150DepreciationMethod(switchToStraightLine: false);
        
        $result = $noSwitchMethod->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 12,
                'currency' => 'USD',
            ]
        );

        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getType_returnsCorrectMethodType(): void
    {
        $this->assertEquals(DepreciationMethodType::DECLINING_150, $this->method->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supportsProrate_returnsTrue(): void
    {
        $this->assertTrue($this->method->supportsProrate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isAccelerated_returnsTrue(): void
    {
        $this->assertTrue($this->method->isAccelerated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withValidParams_returnsTrue(): void
    {
        $result = $this->method->validate(
            cost: 10000.00,
            salvageValue: 1000.00,
            options: [
                'useful_life_months' => 12,
            ]
        );

        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withZeroCost_returnsFalse(): void
    {
        $result = $this->method->validate(
            cost: 0.00,
            salvageValue: 1000.00,
            options: [
                'useful_life_months' => 12,
            ]
        );

        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withNegativeSalvageValue_returnsFalse(): void
    {
        $result = $this->method->validate(
            cost: 10000.00,
            salvageValue: -500.00,
            options: [
                'useful_life_months' => 12,
            ]
        );

        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withZeroUsefulLife_returnsFalse(): void
    {
        $result = $this->method->validate(
            cost: 10000.00,
            salvageValue: 1000.00,
            options: [
                'useful_life_months' => 0,
            ]
        );

        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_withValidParams_returnsEmptyArray(): void
    {
        $errors = $this->method->getValidationErrors(
            cost: 10000.00,
            salvageValue: 1000.00,
            options: [
                'useful_life_months' => 12,
            ]
        );

        $this->assertEmpty($errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_withInvalidCost_returnsError(): void
    {
        $errors = $this->method->getValidationErrors(
            cost: -100.00,
            salvageValue: 1000.00,
            options: [
                'useful_life_months' => 12,
            ]
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Cost must be positive', $errors[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_withNegativeSalvageValue_returnsError(): void
    {
        $errors = $this->method->getValidationErrors(
            cost: 10000.00,
            salvageValue: -100.00,
            options: [
                'useful_life_months' => 12,
            ]
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Salvage value cannot be negative', $errors[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_withZeroUsefulLife_returnsError(): void
    {
        $errors = $this->method->getValidationErrors(
            cost: 10000.00,
            salvageValue: 1000.00,
            options: [
                'useful_life_months' => 0,
            ]
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Useful life months must be positive', $errors[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withValidUsefulLife_returns150PercentRate(): void
    {
        // For 5-year useful life: 1.5 / 5 = 0.3 (30%)
        $rate = $this->method->getDepreciationRate(5);
        
        $this->assertEquals(0.3, $rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withZeroUsefulLife_returnsZero(): void
    {
        $rate = $this->method->getDepreciationRate(0);
        
        $this->assertEquals(0.0, $rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateRemainingDepreciation_returnsCorrectValue(): void
    {
        $remaining = $this->method->calculateRemainingDepreciation(
            currentBookValue: 5000.00,
            salvageValue: 1000.00,
            remainingMonths: 6
        );

        // 5000 - 1000 = 4000
        $this->assertEquals(4000.00, $remaining);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateRemainingDepreciation_withBookValueBelowSalvage_returnsZero(): void
    {
        $remaining = $this->method->calculateRemainingDepreciation(
            currentBookValue: 500.00,
            salvageValue: 1000.00,
            remainingMonths: 6
        );

        // Book value is below salvage, so remaining is 0
        $this->assertEquals(0.0, $remaining);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function requiresUnitsData_returnsFalse(): void
    {
        $this->assertFalse($this->method->requiresUnitsData());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMinimumUsefulLifeMonths_returns12(): void
    {
        $this->assertEquals(12, $this->method->getMinimumUsefulLifeMonths());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_whenSLIsHigher_returnsTrue(): void
    {
        $result = $this->method->shouldSwitchToStraightLine(
            currentBookValue: 5000.00,
            salvageValue: 1000.00,
            remainingMonths: 6,
            decliningBalanceAmount: 50.00
        );

        // SL would be (5000-1000)/6 = 666.67, which is higher than 50
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_whenDBIsHigher_returnsFalse(): void
    {
        $result = $this->method->shouldSwitchToStraightLine(
            currentBookValue: 5000.00,
            salvageValue: 1000.00,
            remainingMonths: 6,
            decliningBalanceAmount: 700.00
        );

        // SL would be (5000-1000)/6 = 666.67, which is lower than 700
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_withZeroRemainingMonths_returnsFalse(): void
    {
        $result = $this->method->shouldSwitchToStraightLine(
            currentBookValue: 5000.00,
            salvageValue: 1000.00,
            remainingMonths: 0,
            decliningBalanceAmount: 100.00
        );

        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDecliningFactor_returns150(): void
    {
        $this->assertEquals(1.5, $this->method->getDecliningFactor());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isSwitchToStraightLineEnabled_withDefault_returnsTrue(): void
    {
        $this->assertTrue($this->method->isSwitchToStraightLineEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isSwitchToStraightLineEnabled_withFalse_returnsFalse(): void
    {
        $noSwitchMethod = new Declining150DepreciationMethod(switchToStraightLine: false);
        $this->assertFalse($noSwitchMethod->isSwitchToStraightLineEnabled());
    }
}
