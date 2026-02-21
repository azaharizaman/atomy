<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\StraightLineDepreciationMethod;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for StraightLineDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class StraightLineDepreciationMethodTest extends TestCase
{
    private StraightLineDepreciationMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = new StraightLineDepreciationMethod();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidInputs_returnsCorrectDepreciation(): void
    {
        // Arrange
        $cost = 12000.0;
        $salvageValue = 2000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        $this->assertEqualsWithDelta(166.67, $result->getAmount(), 0.01, 
            'Monthly depreciation should be (12000-2000)/60');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withAccumulatedDepreciation_respectsRemainingAmount(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 12,
            'accumulated_depreciation' => 8500.0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $remainingDepreciable = $cost - $salvageValue - 8500.0;
        $this->assertLessThanOrEqual($remainingDepreciable, $result->getAmount(),
            'Depreciation should not exceed remaining depreciable amount');
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
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getType_returnsStraightLineEnum(): void
    {
        // Act
        $result = $this->method->getType();

        // Assert
        $this->assertEquals(DepreciationMethodType::STRAIGHT_LINE, $result);
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
    public function isAccelerated_returnsFalse(): void
    {
        // Act
        $result = $this->method->isAccelerated();

        // Assert
        $this->assertFalse($result);
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
    public function validate_withSalvageGreaterThanCost_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(10000.0, 15000.0, ['useful_life_months' => 60]);

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
        $this->assertEquals(0.2, $result);
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
    public function getMinimumUsefulLifeMonths_returnsOne(): void
    {
        // Act
        $result = $this->method->getMinimumUsefulLifeMonths();

        // Assert
        $this->assertEquals(1, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_returnsFalse(): void
    {
        // Act
        $result = $this->method->shouldSwitchToStraightLine(8000.0, 1000.0, 12, 500.0);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withCustomCurrency_returnsCorrectCurrency(): void
    {
        // Arrange
        $options = [
            'useful_life_months' => 60,
            'currency' => 'EUR',
        ];

        // Act
        $result = $this->method->calculate(
            12000.0,
            2000.0,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-31'),
            $options
        );

        // Assert
        $this->assertEquals('EUR', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withProrateDailyEnabled_appliesProration(): void
    {
        // Arrange
        $method = new StraightLineDepreciationMethod(prorateDaily: true);
        $cost = 12000.0;
        $salvageValue = 2000.0;
        $acquisitionDate = new DateTimeImmutable('2026-01-15');
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'currency' => 'USD',
            'acquisition_date' => $acquisitionDate,
            'prorate_daily' => true,
        ];

        // Act
        $result = $method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        $this->assertLessThanOrEqual(166.67, $result->getAmount());
    }
}
