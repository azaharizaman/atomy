<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\Methods\UnitsOfProductionDepreciationMethod;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for UnitsOfProductionDepreciationMethod.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services\Methods
 */
final class UnitsOfProductionDepreciationMethodTest extends TestCase
{
    private UnitsOfProductionDepreciationMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = new UnitsOfProductionDepreciationMethod();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidInputs_returnsCorrectDepreciation(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $totalExpectedUnits = 90000;
        $unitsProduced = 10000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => $unitsProduced,
            'total_expected_units' => $totalExpectedUnits,
            'accumulated_depreciation' => 0.0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
        
        // Depreciation per unit: (100000 - 10000) / 90000 = 1.0 per unit
        // Period depreciation: 1.0 * 10000 = 10000
        $depreciationPerUnit = ($cost - $salvageValue) / $totalExpectedUnits;
        $expected = $depreciationPerUnit * $unitsProduced;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withPartialYearProduction_returnsCorrectDepreciation(): void
    {
        // Arrange
        $cost = 50000.0;
        $salvageValue = 5000.0;
        $totalExpectedUnits = 100000;
        $unitsProduced = 5000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => $unitsProduced,
            'total_expected_units' => $totalExpectedUnits,
            'accumulated_depreciation' => 0.0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $depreciationPerUnit = ($cost - $salvageValue) / $totalExpectedUnits;
        $expected = $depreciationPerUnit * $unitsProduced;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withZeroUnitsProduced_returnsZero(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $totalExpectedUnits = 90000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => 0,
            'total_expected_units' => $totalExpectedUnits,
            'accumulated_depreciation' => 0.0,
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
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $totalExpectedUnits = 90000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => 10000,
            'total_expected_units' => $totalExpectedUnits,
            'accumulated_depreciation' => 90000.0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_doesNotDepreciateBelowSalvageValue(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $totalExpectedUnits = 90000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => 50000,
            'total_expected_units' => $totalExpectedUnits,
            'accumulated_depreciation' => 85000.0,
            'currency' => 'USD',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        // Remaining depreciable = 90000 - 85000 = 5000
        $remainingDepreciable = ($cost - $salvageValue) - 85000.0;
        $this->assertLessThanOrEqual($remainingDepreciable, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getType_returnsUnitsOfProductionEnum(): void
    {
        // Act
        $result = $this->method->getType();

        // Assert
        $this->assertEquals(DepreciationMethodType::UNITS_OF_PRODUCTION, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supportsProrate_returnsFalse(): void
    {
        // Act
        $result = $this->method->supportsProrate();

        // Assert
        $this->assertFalse($result);
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
        $result = $this->method->validate(
            100000.0,
            10000.0,
            ['total_expected_units' => 90000, 'units_produced' => 10000]
        );

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withNegativeCost_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(
            -100.0,
            1000.0,
            ['total_expected_units' => 90000]
        );

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withSalvageGreaterThanCost_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(
            10000.0,
            15000.0,
            ['total_expected_units' => 90000]
        );

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withZeroTotalUnits_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(
            100000.0,
            10000.0,
            ['total_expected_units' => 0]
        );

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withNegativeUnitsProduced_returnsFalse(): void
    {
        // Act
        $result = $this->method->validate(
            100000.0,
            10000.0,
            ['total_expected_units' => 90000, 'units_produced' => -100]
        );

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_returnsEmptyArrayForValidInput(): void
    {
        // Act
        $result = $this->method->getValidationErrors(
            100000.0,
            10000.0,
            ['total_expected_units' => 90000]
        );

        // Assert
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValidationErrors_returnsErrorsForInvalidInput(): void
    {
        // Act
        $result = $this->method->getValidationErrors(
            -100.0,
            1000.0,
            ['total_expected_units' => 0]
        );

        // Assert
        $this->assertNotEmpty($result);
        $this->assertContains('Cost must be positive', $result);
        $this->assertContains('Total expected units must be positive for UOP method', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withValidInputs_returnsCorrectRate(): void
    {
        // Act
        $result = $this->method->getDepreciationRate(5, [
            'cost' => 100000.0,
            'salvage_value' => 10000.0,
            'total_expected_units' => 90000
        ]);

        // Assert
        // Rate per unit: (100000 - 10000) / 90000 = 1.0
        $this->assertEquals(1.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withZeroUnits_returnsZero(): void
    {
        // Act
        $result = $this->method->getDepreciationRate(5, [
            'cost' => 100000.0,
            'salvage_value' => 10000.0,
            'total_expected_units' => 0
        ]);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateRemainingDepreciation_returnsCorrectAmount(): void
    {
        // Act
        $result = $this->method->calculateRemainingDepreciation(80000.0, 10000.0, 60);

        // Assert
        $this->assertEquals(70000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function requiresUnitsData_returnsTrue(): void
    {
        // Act
        $result = $this->method->requiresUnitsData();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMinimumUsefulLifeMonths_returnsZero(): void
    {
        // Act
        $result = $this->method->getMinimumUsefulLifeMonths();

        // Assert
        $this->assertEquals(0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldSwitchToStraightLine_returnsFalse(): void
    {
        // Act
        $result = $this->method->shouldSwitchToStraightLine(80000.0, 10000.0, 60, 5000.0);

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationPerUnit_withValidInputs_returnsCorrectAmount(): void
    {
        // Act
        $result = $this->method->getDepreciationPerUnit(100000.0, 10000.0, 90000);

        // Assert
        $this->assertEquals(1.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationPerUnit_withZeroUnits_returnsZero(): void
    {
        // Act
        $result = $this->method->getDepreciationPerUnit(100000.0, 10000.0, 0);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withAccumulatedDepreciation_updatesAccumulatedCorrectly(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $totalExpectedUnits = 90000;
        $unitsProduced = 10000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $accumulatedDepreciation = 20000.0;
        $options = [
            'units_produced' => $unitsProduced,
            'total_expected_units' => $totalExpectedUnits,
            'accumulated_depreciation' => $accumulatedDepreciation,
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
    public function calculate_withHoursUnitType_appliesCorrectly(): void
    {
        // Arrange - using hours instead of units
        $cost = 50000.0;
        $salvageValue = 5000.0;
        $totalExpectedHours = 10000;
        $hoursUsed = 1000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => $hoursUsed,
            'total_expected_units' => $totalExpectedHours,
            'accumulated_depreciation' => 0.0,
            'currency' => 'USD',
            'unit_type' => 'hours',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $depreciationPerUnit = ($cost - $salvageValue) / $totalExpectedHours;
        $expected = $depreciationPerUnit * $hoursUsed;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withMilesUnitType_appliesCorrectly(): void
    {
        // Arrange - using miles for vehicle depreciation
        $cost = 30000.0;
        $salvageValue = 5000.0;
        $totalExpectedMiles = 100000;
        $milesDriven = 15000;
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        $options = [
            'units_produced' => $milesDriven,
            'total_expected_units' => $totalExpectedMiles,
            'accumulated_depreciation' => 0.0,
            'currency' => 'USD',
            'unit_type' => 'miles',
        ];

        // Act
        $result = $this->method->calculate($cost, $salvageValue, $startDate, $endDate, $options);

        // Assert
        $depreciationPerUnit = ($cost - $salvageValue) / $totalExpectedMiles;
        $expected = $depreciationPerUnit * $milesDriven;
        
        $this->assertEqualsWithDelta($expected, $result->getAmount(), 0.01);
    }
}
