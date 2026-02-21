<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Test cases for BookValue value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class BookValueTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        // Act
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Assert
        $this->assertEquals(10000.0, $bookValue->cost);
        $this->assertEquals(1000.0, $bookValue->salvageValue);
        $this->assertEquals(3000.0, $bookValue->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getNetBookValue_returnsCostMinusAccumulated(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $result = $bookValue->getNetBookValue();

        // Assert
        $this->assertEquals(7000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getNetBookValue_withZeroAccumulated_returnsCost(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 0.0
        );

        // Act
        $result = $bookValue->getNetBookValue();

        // Assert
        $this->assertEquals(10000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciableAmount_returnsCostMinusSalvage(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $result = $bookValue->getDepreciableAmount();

        // Assert
        $this->assertEquals(9000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciated_whenAccumulatedEqualsDepreciable_returnsTrue(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 9000.0
        );

        // Act
        $result = $bookValue->isFullyDepreciated();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciated_whenAccumulatedExceedsDepreciable_returnsTrue(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 9500.0
        );

        // Act
        $result = $bookValue->isFullyDepreciated();

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciated_whenAccumulatedLessThanDepreciable_returnsFalse(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $result = $bookValue->isFullyDepreciated();

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciate_withValidAmount_updatesAccumulated(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );
        $depreciationAmount = new DepreciationAmount(500.0, 'USD');

        // Act
        $newBookValue = $bookValue->depreciate($depreciationAmount);

        // Assert
        $this->assertEquals(3500.0, $newBookValue->accumulatedDepreciation);
        $this->assertEquals(6500.0, $newBookValue->getNetBookValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciate_doesNotExceedDepreciableAmount(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 8000.0
        );
        $depreciationAmount = new DepreciationAmount(2000.0, 'USD');

        // Act
        $newBookValue = $bookValue->depreciate($depreciationAmount);

        // Assert
        // Should cap at depreciable amount (9000)
        $this->assertEquals(9000.0, $newBookValue->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciate_preservesCostAndSalvage(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );
        $depreciationAmount = new DepreciationAmount(500.0, 'USD');

        // Act
        $newBookValue = $bookValue->depreciate($depreciationAmount);

        // Assert
        $this->assertEquals(10000.0, $newBookValue->cost);
        $this->assertEquals(1000.0, $newBookValue->salvageValue);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciate_createsNewInstance(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );
        $depreciationAmount = new DepreciationAmount(500.0, 'USD');

        // Act
        $newBookValue = $bookValue->depreciate($depreciationAmount);

        // Assert
        $this->assertNotSame($bookValue, $newBookValue);
        $this->assertEquals(3000.0, $bookValue->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_withNewValues_returnsUpdatedBookValue(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $newBookValue = $bookValue->revalue(15000.0, 2000.0);

        // Assert
        $this->assertEquals(15000.0, $newBookValue->cost);
        $this->assertEquals(2000.0, $newBookValue->salvageValue);
        $this->assertEquals(3000.0, $newBookValue->accumulatedDepreciation);
        $this->assertEquals(12000.0, $newBookValue->getNetBookValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_preservesAccumulatedDepreciation(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 5000.0
        );

        // Act
        $newBookValue = $bookValue->revalue(20000.0, 3000.0);

        // Assert
        $this->assertEquals(5000.0, $newBookValue->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_createsNewInstance(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $newBookValue = $bookValue->revalue(15000.0, 2000.0);

        // Assert
        $this->assertNotSame($bookValue, $newBookValue);
        $this->assertEquals(10000.0, $bookValue->cost);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRemainingDepreciableAmount_returnsCorrectValue(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $result = $bookValue->getRemainingDepreciableAmount();

        // Assert
        $this->assertEquals(6000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRemainingDepreciableAmount_whenFullyDepreciated_returnsZero(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 9000.0
        );

        // Act
        $result = $bookValue->getRemainingDepreciableAmount();

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRemainingDepreciableAmount_whenOverDepreciated_returnsZero(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 10000.0
        );

        // Act
        $result = $bookValue->getRemainingDepreciableAmount();

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function format_returnsFormattedString(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        // Act
        $result = $bookValue->format();

        // Assert
        $this->assertStringContainsString('10,000.00', $result);
        $this->assertStringContainsString('1,000.00', $result);
        $this->assertStringContainsString('3,000.00', $result);
        $this->assertStringContainsString('7,000.00', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciate_multipleTimes_chainsCorrectly(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 0.0
        );
        $depreciationAmount1 = new DepreciationAmount(500.0, 'USD');
        $depreciationAmount2 = new DepreciationAmount(500.0, 'USD');
        $depreciationAmount3 = new DepreciationAmount(500.0, 'USD');

        // Act
        $afterFirst = $bookValue->depreciate($depreciationAmount1);
        $afterSecond = $afterFirst->depreciate($depreciationAmount2);
        $afterThird = $afterSecond->depreciate($depreciationAmount3);

        // Assert
        $this->assertEquals(500.0, $afterFirst->accumulatedDepreciation);
        $this->assertEquals(1000.0, $afterSecond->accumulatedDepreciation);
        $this->assertEquals(1500.0, $afterThird->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_afterDepreciation_maintainsCorrectRelationships(): void
    {
        // Arrange
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );
        $depreciationAmount = new DepreciationAmount(500.0, 'USD');

        // Act
        $depreciated = $bookValue->depreciate($depreciationAmount);
        $revalued = $depreciated->revalue(15000.0, 1500.0);

        // Assert
        $this->assertEquals(15000.0, $revalued->cost);
        $this->assertEquals(1500.0, $revalued->salvageValue);
        $this->assertEquals(3500.0, $revalued->accumulatedDepreciation);
        $this->assertEquals(11500.0, $revalued->getNetBookValue());
    }
}
