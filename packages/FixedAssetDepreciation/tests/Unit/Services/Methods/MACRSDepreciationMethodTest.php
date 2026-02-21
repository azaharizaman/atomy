<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\MACRSDepreciationMethod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for MACRSDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class MACRSDepreciationMethodTest extends TestCase
{
    private MACRSDepreciationMethod $method;

    protected function setUp(): void
    {
        $this->method = new MACRSDepreciationMethod();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithDefaultValues(): void
    {
        $method = new MACRSDepreciationMethod();
        
        $this->assertInstanceOf(MACRSDepreciationMethod::class, $method);
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
                'useful_life_months' => 60,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 60,
                'currency' => 'USD',
                'recovery_period' => 5,
                'property_class' => '7-year',
            ]
        );

        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount::class, $result);
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
                'useful_life_months' => 60,
                'accumulated_depreciation' => 3000.00,
                'remaining_months' => 48,
                'currency' => 'USD',
                'recovery_period' => 5,
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
    public function getType_returnsCorrectMethodType(): void
    {
        $this->assertEquals(DepreciationMethodType::MACRS, $this->method->getType());
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
                'useful_life_months' => 60,
                'recovery_period' => 5,
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
                'useful_life_months' => 60,
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
                'useful_life_months' => 60,
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
                'useful_life_months' => 60,
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
                'useful_life_months' => 60,
            ]
        );

        $this->assertNotEmpty($errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function requiresUnitsData_returnsFalse(): void
    {
        $this->assertFalse($this->method->requiresUnitsData());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMinimumUsefulLifeMonths_returns60(): void
    {
        // MACRS typically has minimum 5-year recovery period
        $this->assertEquals(60, $this->method->getMinimumUsefulLifeMonths());
    }
}
