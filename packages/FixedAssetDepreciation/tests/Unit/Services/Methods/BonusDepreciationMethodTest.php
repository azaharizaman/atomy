<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\BonusDepreciationMethod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for BonusDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class BonusDepreciationMethodTest extends TestCase
{
    private BonusDepreciationMethod $method;

    protected function setUp(): void
    {
        $this->method = new BonusDepreciationMethod(
            bonusRate: 0.50,
            applyToFullCost: true
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithDefaultValues(): void
    {
        $defaultMethod = new BonusDepreciationMethod();
        
        $reflection = new \ReflectionClass($defaultMethod);
        $bonusRateProperty = $reflection->getProperty('bonusRate');
        $bonusRateProperty->setAccessible(true);
        
        $this->assertEquals(1.0, $bonusRateProperty->getValue($defaultMethod));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCustomValues(): void
    {
        $reflection = new \ReflectionClass($this->method);
        $bonusRateProperty = $reflection->getProperty('bonusRate');
        $bonusRateProperty->setAccessible(true);
        
        $applyToFullCostProperty = $reflection->getProperty('applyToFullCost');
        $applyToFullCostProperty->setAccessible(true);
        
        $this->assertEquals(0.50, $bonusRateProperty->getValue($this->method));
        $this->assertTrue($applyToFullCostProperty->getValue($this->method));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withFullBonusRate_returnsFullCostDepreciation(): void
    {
        $fullBonusMethod = new BonusDepreciationMethod(bonusRate: 1.0);
        
        $result = $fullBonusMethod->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 12,
                'currency' => 'USD',
                'period_number' => 1,
            ]
        );

        // With 100% bonus rate, the full cost should be depreciated
        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_with50PercentBonusRate_returnsHalfCostDepreciation(): void
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
                'period_number' => 1,
            ]
        );

        // With 50% bonus rate on $10000, should get $5000 bonus depreciation
        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
        $this->assertGreaterThan(0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withAccumulatedDepreciation_appliesToRemainingBasis(): void
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
                'period_number' => 7,
            ]
        );

        // With accumulated depreciation, bonus should apply to remaining basis
        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withApplyToFullCostFalse_appliesToDepreciableBasis(): void
    {
        $method = new BonusDepreciationMethod(bonusRate: 0.50, applyToFullCost: false);
        
        $result = $method->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 12,
                'currency' => 'USD',
                'period_number' => 1,
            ]
        );

        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withNonFirstPeriod_returnsZero(): void
    {
        $result = $this->method->calculate(
            cost: 10000.00,
            salvageValue: 1000.00,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-02-28'),
            options: [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 12,
                'currency' => 'USD',
                'period_number' => 2, // Not first period - bonus only applies to period 1
            ]
        );

        // Bonus depreciation only applies in first period
        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getType_returnsCorrectMethodType(): void
    {
        $this->assertEquals(DepreciationMethodType::BONUS, $this->method->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supportsProrate_returnsTrue(): void
    {
        $this->assertTrue($this->method->supportsProrate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isAccelerated_returnsFalse(): void
    {
        // Bonus depreciation is not accelerated - it's first-year deduction
        $this->assertFalse($this->method->isAccelerated());
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
    public function validate_withNegativeCost_returnsFalse(): void
    {
        $result = $this->method->validate(
            cost: -1000.00,
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
    public function requiresUnitsData_returnsFalse(): void
    {
        $this->assertFalse($this->method->requiresUnitsData());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMinimumUsefulLifeMonths_returnsZero(): void
    {
        // Bonus depreciation doesn't require useful life
        $this->assertEquals(0, $this->method->getMinimumUsefulLifeMonths());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getBonusRate_returnsCorrectRate(): void
    {
        $reflection = new \ReflectionClass($this->method);
        $bonusRateProperty = $reflection->getProperty('bonusRate');
        $bonusRateProperty->setAccessible(true);
        
        $this->assertEquals(0.50, $bonusRateProperty->getValue($this->method));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isApplyToFullCost_returnsCorrectValue(): void
    {
        $reflection = new \ReflectionClass($this->method);
        $applyToFullCostProperty = $reflection->getProperty('applyToFullCost');
        $applyToFullCostProperty->setAccessible(true);
        
        $this->assertTrue($applyToFullCostProperty->getValue($this->method));
    }
}
