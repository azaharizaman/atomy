<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Methods;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Methods\StraightLineDepreciationMethod;
use Nexus\FixedAssetDepreciation\Methods\DoubleDecliningDepreciationMethod;
use Nexus\FixedAssetDepreciation\Methods\SumOfYearsDepreciationMethod;
use Nexus\FixedAssetDepreciation\Methods\UnitsOfProductionDepreciationMethod;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for depreciation calculation methods.
 */
final class DepreciationMethodsTest extends TestCase
{
    public function testStraightLineCalculatesCorrectly(): void
    {
        $method = new StraightLineDepreciationMethod();

        $cost = 12000.0;
        $salvageValue = 2000.0;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            ['useful_life_months' => 60]
        );

        $this->assertEqualsWithDelta(166.67, $result->amount, 0.01);
        $this->assertEquals(DepreciationMethodType::STRAIGHT_LINE, $method->getType());
        $this->assertFalse($method->isAccelerated());
        $this->assertTrue($method->supportsProrate());
    }

    public function testStraightLineValidatesCorrectly(): void
    {
        $method = new StraightLineDepreciationMethod();

        $this->assertTrue($method->validate(10000.0, 1000.0, ['useful_life_months' => 60]));
        $this->assertFalse($method->validate(-100.0, 1000.0, ['useful_life_months' => 60]));
        $this->assertFalse($method->validate(10000.0, 15000.0, ['useful_life_months' => 60]));
        $this->assertFalse($method->validate(10000.0, 1000.0, ['useful_life_months' => 0]));
    }

    public function testStraightLineRespectsAccumulatedDepreciation(): void
    {
        $method = new StraightLineDepreciationMethod();

        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            [
                'useful_life_months' => 12,
                'accumulated_depreciation' => 8500.0
            ]
        );

        $remainingDepreciable = $cost - $salvageValue - 8500.0;
        $this->assertEqualsWithDelta(500.0, $remainingDepreciable, 0.01);
        $this->assertLessThanOrEqual($remainingDepreciable, $result->amount);
    }

    public function testDoubleDecliningCalculatesCorrectly(): void
    {
        $method = new DoubleDecliningDepreciationMethod();

        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            [
                'useful_life_months' => 60,
                'accumulated_depreciation' => 0.0,
                'remaining_months' => 60
            ]
        );

        $annualRate = 2.0 / 5;
        $monthlyRate = $annualRate / 12;
        $expected = $cost * $monthlyRate;

        $this->assertEqualsWithDelta($expected, $result->amount, 0.01);
        $this->assertEquals(DepreciationMethodType::DOUBLE_DECLINING, $method->getType());
        $this->assertTrue($method->isAccelerated());
    }

    public function testDoubleDecliningNeverDepreciatesBelowSalvage(): void
    {
        $method = new DoubleDecliningDepreciationMethod();

        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            [
                'useful_life_months' => 60,
                'accumulated_depreciation' => 8900.0,
                'remaining_months' => 1
            ]
        );

        $remainingDepreciable = $cost - $salvageValue - 8900.0;
        $this->assertLessThanOrEqual($remainingDepreciable, $result->amount);
    }

    public function testSumOfYearsCalculatesCorrectly(): void
    {
        $method = new SumOfYearsDepreciationMethod();

        $cost = 10000.0;
        $salvageValue = 1000.0;
        $depreciableAmount = $cost - $salvageValue;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            [
                'useful_life_months' => 60,
                'accumulated_depreciation' => 0.0,
                'current_year' => 1
            ]
        );

        $sumOfYears = (5 * 6) / 2;
        $yearlyDepreciation = ($depreciableAmount * 5) / $sumOfYears;
        $monthlyDepreciation = $yearlyDepreciation / 12;

        $this->assertEqualsWithDelta($monthlyDepreciation, $result->amount, 0.01);
        $this->assertEquals(DepreciationMethodType::SUM_OF_YEARS, $method->getType());
        $this->assertTrue($method->isAccelerated());
    }

    public function testUnitsOfProductionCalculatesCorrectly(): void
    {
        $method = new UnitsOfProductionDepreciationMethod();

        $cost = 10000.0;
        $salvageValue = 1000.0;
        $totalExpectedUnits = 100000;
        $unitsProduced = 2000;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            [
                'units_produced' => $unitsProduced,
                'total_expected_units' => $totalExpectedUnits,
                'accumulated_depreciation' => 0.0
            ]
        );

        $depreciationPerUnit = ($cost - $salvageValue) / $totalExpectedUnits;
        $expected = $depreciationPerUnit * $unitsProduced;

        $this->assertEqualsWithDelta($expected, $result->amount, 0.01);
        $this->assertEquals(DepreciationMethodType::UNITS_OF_PRODUCTION, $method->getType());
        $this->assertFalse($method->isAccelerated());
        $this->assertTrue($method->requiresUnitsData());
    }

    public function testUnitsOfProductionRequiresUnitsData(): void
    {
        $method = new UnitsOfProductionDepreciationMethod();

        $this->assertFalse($method->validate(
            10000.0,
            1000.0,
            ['total_expected_units' => 0]
        ));

        $errors = $method->getValidationErrors(
            10000.0,
            1000.0,
            ['total_expected_units' => 0]
        );

        $this->assertNotEmpty($errors);
    }

    public function testMethodReturnsZeroWhenFullyDepreciated(): void
    {
        $method = new StraightLineDepreciationMethod();

        $cost = 10000.0;
        $salvageValue = 1000.0;
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');

        $result = $method->calculate(
            $cost,
            $salvageValue,
            $startDate,
            $endDate,
            [
                'useful_life_months' => 60,
                'accumulated_depreciation' => 9000.0
            ]
        );

        $this->assertEquals(0.0, $result->amount);
    }
}
