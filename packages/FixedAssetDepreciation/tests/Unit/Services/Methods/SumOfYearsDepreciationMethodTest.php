<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\SumOfYearsDepreciationMethod;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for SumOfYearsDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class SumOfYearsDepreciationMethodTest extends TestCase
{
    private SumOfYearsDepreciationMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = new SumOfYearsDepreciationMethod();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidInputs_returnsCorrectDepreciation(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $depreciableAmount = $cost - $salvageValue;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'current_year' => 1,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        
        // SYD for 5-year asset: sum = 5+4+3+2+1 = 15
        // Year 1: 5/15 of depreciable amount per year
        $sumOfYears = (5 * 6) / 2;
        $yearlyDepreciation = ($depreciableAmount * 5) / $sumOfYears;
        $monthlyDepreciation = $yearlyDepreciation / 12;
        
        $this->assertEqualsWithDelta($monthlyDepreciation, $result->getAmount(), 1.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withSecondYear_decreasesDepreciation(): void
    {
        // Arrange
        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        
        // First year
        $optionsYear1 = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'current_year' => 1,
            'currency' => 'USD',
        ];
        
        // Second year
        $optionsYear2 = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'current_year' => 2,
            'currency' => 'USD',
        ];

        // Act
        $resultYear1 = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $optionsYear1);
        $resultYear2 = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $optionsYear2);

        // Assert
        $this->assertGreaterThan($resultYear2->getAmount(), $resultYear1->getAmount(),
            'Second year depreciation should be less than first year');
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
    public function getType_returnsSumOfYearsEnum(): void
    {
        // Act
        $result = $this->method->getType();

        // Assert
        $this->assertEquals(DepreciationMethodType::SUM_OF_YEARS, $result);
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
    public function validate_withSalvageGreaterThanCost_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(10000.0, 15000.0, ['useful_life_months' => 60]);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withLessThan12Months_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(10000.0, 1000.0, ['useful_life_months' => 6]);

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
    public function getDepreciationRate_firstYear_returnsCorrectFraction(): void
    {
        // Act
        $result = $this->method->getDepreciationRate(5, ['current_year' => 1]);

        // Assert
        // First year: remaining life = 5, sum = 15, fraction = 5/15 = 0.3333
        $this->assertEqualsWithDelta(0.3333, $result, 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_secondYear_returnsCorrectFraction(): void
    {
        // Act
        $result = $this->method->getDepreciationRate(5, ['current_year' => 2]);

        // Assert
        // Second year: remaining life = 4, sum = 15, fraction = 4/15 = 0.2667
        $this->assertEqualsWithDelta(0.2667, $result, 0.01);
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
    public function shouldSwitchToStraightLine_returnsFalse(): void
    {
        // Act
        $result = $this->method->shouldSwitchToStraightLine(8000.0, 1000.0, 12, 500.0);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSumOfYears_fiveYearAsset_returnsFifteen(): void
    {
        // Act
        $result = $this->method->getSumOfYears(5);

        // Assert
        $this->assertEquals(15, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSumOfYears_threeYearAsset_returnsSix(): void
    {
        // Act
        $result = $this->method->getSumOfYears(3);

        // Assert
        $this->assertEquals(6, $result);
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
            'current_year' => 1,
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withCustomCurrency_returnsCorrectCurrency(): void
    {
        // Arrange
        $options = [
            'useful_life_months' => 60,
            'accumulated_depreciation' => 0.0,
            'current_year' => 1,
            'currency' => 'EUR',
        ];

        // Act
        $result = $this->method->calculate(
            10000.0,
            2000.0,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-31'),
            $options
        );

        // Assert
        $this->assertEquals('EUR', $result->currency);
    }
}
