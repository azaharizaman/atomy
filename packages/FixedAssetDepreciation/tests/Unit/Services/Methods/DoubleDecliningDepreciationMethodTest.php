<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\DoubleDecliningDepreciationMethod;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for DoubleDecliningDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class DoubleDecliningDepreciationMethodTest extends TestCase
{
    private DoubleDecliningDepreciationMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = new DoubleDecliningDepreciationMethod();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidInputs_returnsCorrectDepreciation(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'remaining_months' => 60,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        
        // Double declining: rate = 2/5 = 0.4 per year, 0.4/12 per month
        $annualRate = 2.0 / 5;
        $monthlyRate = $annualRate / 12;
        $expected = $cost * $monthlyRate;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 1.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_neverDepreciatesBelowSalvageValue(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 8900.0,
            'remaining_months' => 1,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $remainingDepreciable = $cost - $salvageValue - 8900.0;
        $this->assertLessThanOrEqual($remainingDepreciable, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withZeroUsefulLife_returnsZero(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_whenFullyDepreciated_returnsZero(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 9000.0,
            'remaining_months' => 0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getType_returnsDoubleDecliningEnum(): void
    {
        // Act
        $result = $this->method->getType();

        // Assert
        $this->assertEquals(DepreciationMethodType::DOUBLE_DECLINING, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supportsProrate_returnsTrue(): void
    {
        // Act
        $result = $this->method->supportsProrate();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isAccelerated_returnsTrue(): void
    {
        // Act
        $result = $this->method->isAccelerated();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withValidInputs_returnsTrue(): void
    {
        // Act
        $result = $this->method->validate(10000.0, 1000.0, ['useful_life_months' => 60]);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withNegativeCost_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(-100.0, 1000.0, ['useful_life_months' => 60]);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withZeroUsefulLife_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(10000.0, 1000.0, ['useful_life_months' => 0]);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_returnsEmptyArrayForValidInput(): void
    {
        // Act
        $result = $this->method->getValidationErrors(10000.0, 1000.0, ['useful_life_months' => 60]);

        // Assert
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_returnsErrorsForInvalidInput(): void
    {
        // Act
        $result = $this->method->getValidationErrors(-100.0, 1000.0, ['useful_life_months' => 60]);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertContains('Cost must be positive', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withValidYears_returnsCorrectRate(): void
    {
        // Act
        $result = $this->method->getDepreciationRate(5);

        // Assert
        // Double declining: 2/5 = 0.4
        $this->assertEquals(0.4, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withZeroYears_returnsZero(): void
    {
        // Act
        $result = $this->method->getDepreciationRate(0);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateRemainingDepreciation_returnsCorrectAmount(): void
    {
        // Act
        $result = $this->method->calculateRemainingDepreciation(8000.0, 1000.0, 12);

        // Assert
        $this->assertEquals(7000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function requiresUnitsData_returnsFalse(): void
    {
        // Act
        $result = $this->method->requiresUnitsData();

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMinimumUsefulLifeMonths_returnsTwelve(): void
    {
        // Act
        $result = $this->method->getMinimumUsefulLifeMonths();

        // Assert
        $this->assertEquals(12, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_whenSlHigherThanDdb_returnsTrue(): void
    {
        // Arrange - SL amount: (8000-1000)/12 = 583.33
        // DDB amount: 8000 * (2/5/12) = 266.67

        // Act
        $result = $this->method->shouldSwitchToStraightLine(8000.0, 1000.0, 12, 266.67);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_whenDdbHigherThanSl_returnsFalse(): void
    {
        // Arrange - SL amount: (10000-1000)/60 = 150
        // DDB amount: 10000 * (2/5/12) = 333.33

        // Act
        $result = $this->method->shouldSwitchToStraightLine(10000.0, 1000.0, 60, 333.33);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDecliningFactor_returnsDefaultValue(): void
    {
        // Act
        $result = $this->method->getDecliningFactor();

        // Assert
        $this->assertEquals(2.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isSwitchToStraightLineEnabled_returnsTrueByDefault(): void
    {
        // Act
        $result = $this->method->isSwitchToStraightLineEnabled();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withCustomFactor_usesCustomFactor(): void
    {
        // Arrange
        $method = new DoubleDecliningDepreciationMethod(decliningFactor: 1.5);
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'remaining_months' => 60,
            'currency' => 'USD',
        ];

        // Act
        $result = $method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        // 150% declining: rate = 1.5/5 = 0.3 per year
        $annualRate = 1.5 / 5;
        $monthlyRate = $annualRate / 12;
        $expected = $cost * $monthlyRate;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 1.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withSwitchDisabled_doesNotSwitchToStraightLine(): void
    {
        // Arrange
        $method = new DoubleDecliningDepreciationMethod(switchToStraightLine: false);
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'remaining_months' => 60,
            'currency' => 'USD',
        ];

        // Act
        $result = $method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        // Without switch, should use pure DDB
        $annualRate = 2.0 / 5;
        $monthlyRate = $annualRate / 12;
        $expected = $cost * $monthlyRate;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 1.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withAccumulatedDepreciation_updatesAccumulatedCorrectly(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $accumulatedDepreciation = 3000.0;
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'remaining_months' => 42,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertEqualsWithDelta(
            $accumulatedDepreciation + $result->getAmount(),
            $result->accumulatedDepreciation,
            0.01
        );
    }
}
