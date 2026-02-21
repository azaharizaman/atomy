<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;

/**
 * Test cases for DepreciationLife value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class DepreciationLifeTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        // Act
        $life = new DepreciationLife(
            usefulLifeYears: 5,
            usefulLifeMonths: 60,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Assert
        $this->assertEquals(5, $life->usefulLifeYears);
        $this->assertEquals(60, $life->usefulLifeMonths);
        $this->assertEquals(1000.0, $life->salvageValue);
        $this->assertEquals(9000.0, $life->totalDepreciableAmount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromYears_createsInstanceWithCorrectValues(): void
    {
        // Act
        $life = DepreciationLife::fromYears(5, 10000.0, 1000.0);

        // Assert
        $this->assertEquals(5, $life->usefulLifeYears);
        $this->assertEquals(60, $life->usefulLifeMonths);
        $this->assertEquals(1000.0, $life->salvageValue);
        $this->assertEquals(9000.0, $life->totalDepreciableAmount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromYears_withZeroYears_setsZeroMonths(): void
    {
        // Act
        $life = DepreciationLife::fromYears(0, 10000.0, 1000.0);

        // Assert
        $this->assertEquals(0, $life->usefulLifeYears);
        $this->assertEquals(0, $life->usefulLifeMonths);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromYears_withOneYear_setsTwelveMonths(): void
    {
        // Act
        $life = DepreciationLife::fromYears(1, 10000.0, 1000.0);

        // Assert
        $this->assertEquals(1, $life->usefulLifeYears);
        $this->assertEquals(12, $life->usefulLifeMonths);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTotalMonths_returnsCorrectValue(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 5,
            usefulLifeMonths: 60,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Act
        $result = $life->getTotalMonths();

        // Assert
        $this->assertEquals(60, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMonthlyDepreciation_withValidLife_returnsCorrectAmount(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 5,
            usefulLifeMonths: 60,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Act
        $result = $life->getMonthlyDepreciation();

        // Assert
        $this->assertEquals(150.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMonthlyDepreciation_withZeroMonths_returnsZero(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 0,
            usefulLifeMonths: 0,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Act
        $result = $life->getMonthlyDepreciation();

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAnnualDepreciation_withValidLife_returnsCorrectAmount(): void
    {
        // Arrange
        $life = DepreciationLife::fromYears(5, 10000.0, 1000.0);

        // Act
        $result = $life->getAnnualDepreciation();

        // Assert
        // Monthly: 150, Annual: 150 * 12 = 1800
        $this->assertEquals(1800.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAnnualDepreciation_withZeroMonths_returnsZero(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 0,
            usefulLifeMonths: 0,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Act
        $result = $life->getAnnualDepreciation();

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isValid_withPositiveMonthsAndAmount_returnsTrue(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 5,
            usefulLifeMonths: 60,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Act
        $result = $life->isValid();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isValid_withZeroMonths_returnsFalse(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 0,
            usefulLifeMonths: 0,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        // Act
        $result = $life->isValid();

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isValid_withZeroDepreciableAmount_returnsFalse(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 5,
            usefulLifeMonths: 60,
            salvageValue: 10000.0,
            totalDepreciableAmount: 0.0
        );

        // Act
        $result = $life->isValid();

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isValid_withNegativeDepreciableAmount_returnsFalse(): void
    {
        // Arrange
        $life = new DepreciationLife(
            usefulLifeYears: 5,
            usefulLifeMonths: 60,
            salvageValue: 15000.0,
            totalDepreciableAmount: -5000.0
        );

        // Act
        $result = $life->isValid();

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromYears_withDifferentCosts_calculatesCorrectDepreciableAmount(): void
    {
        // Act
        $life = DepreciationLife::fromYears(3, 30000.0, 3000.0);

        // Assert
        $this->assertEquals(27000.0, $life->totalDepreciableAmount);
        $this->assertEquals(750.0, $life->getMonthlyDepreciation());
        $this->assertEquals(9000.0, $life->getAnnualDepreciation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromYears_withZeroCost_createsInvalidLife(): void
    {
        // Act
        $life = DepreciationLife::fromYears(5, 0.0, 0.0);

        // Assert
        $this->assertFalse($life->isValid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromYears_withSalvageEqualsCost_createsInvalidLife(): void
    {
        // Act
        $life = DepreciationLife::fromYears(5, 10000.0, 10000.0);

        // Assert
        $this->assertEquals(0.0, $life->totalDepreciableAmount);
        $this->assertFalse($life->isValid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMonthlyDepreciation_fractionalMonths_handlesCorrectly(): void
    {
        // Arrange - 7 year asset
        $life = DepreciationLife::fromYears(7, 70000.0, 7000.0);

        // Act
        $monthly = $life->getMonthlyDepreciation();

        // Assert
        // (70000 - 7000) / (7 * 12) = 63000 / 84 = 750
        $this->assertEquals(750.0, $monthly);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAnnualDepreciation_isTwelveTimesMonthly(): void
    {
        // Arrange
        $life = DepreciationLife::fromYears(5, 10000.0, 1000.0);

        // Act
        $monthly = $life->getMonthlyDepreciation();
        $annual = $life->getAnnualDepreciation();

        // Assert
        $this->assertEquals($monthly * 12, $annual);
    }
}
