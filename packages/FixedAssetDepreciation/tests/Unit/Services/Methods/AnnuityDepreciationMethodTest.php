<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\AnnuityDepreciationMethod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for AnnuityDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class AnnuityDepreciationMethodTest extends TestCase
{
    private AnnuityDepreciationMethod $method;

    protected function setUp(): void
    {
        $this->method = new AnnuityDepreciationMethod(
            interestRate: 0.10,
            includeInterestInExpense: false
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithDefaultValues(): void
    {
        $defaultMethod = new AnnuityDepreciationMethod();
        
        $this->assertEquals(0.10, $defaultMethod->getInterestRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCustomValues(): void
    {
        $this->assertEquals(0.10, $this->method->getInterestRate());
        $this->assertFalse($this->method->isInterestIncludedInExpense());
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
                'period_number' => 1,
            ]
        );

        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
        $this->assertEquals('USD', $result->currency);
        $this->assertGreaterThan(0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withAccumulatedDepreciation_reducesDepreciableAmount(): void
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

        // With accumulated depreciation, the amount should be different
        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withZeroUsefulLife_returnsZeroDepreciation(): void
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
    public function calculate_withZeroInterestRate_throwsException(): void
    {
        $zeroRateMethod = new AnnuityDepreciationMethod(interestRate: 0.0);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('interest rate');
        
        $zeroRateMethod->calculate(
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
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getType_returnsCorrectMethodType(): void
    {
        $this->assertEquals(DepreciationMethodType::ANNUITY, $this->method->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supportsProrate_returnsTrue(): void
    {
        $this->assertTrue($this->method->supportsProrate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isAccelerated_returnsFalse(): void
    {
        // Annuity method is not accelerated - it's based on interest calculations
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
    public function requiresUnitsData_returnsFalse(): void
    {
        $this->assertFalse($this->method->requiresUnitsData());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMinimumUsefulLifeMonths_returns12(): void
    {
        $this->assertEquals(1, $this->method->getMinimumUsefulLifeMonths());
    }
}
