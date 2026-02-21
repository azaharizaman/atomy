<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Test cases for DepreciationAmount value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class DepreciationAmountTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        // Act
        $amount = new DepreciationAmount(100.0, 'USD', 500.0);

        // Assert
        $this->assertEquals(100.0, $amount->amount);
        $this->assertEquals('USD', $amount->currency);
        $this->assertEquals(500.0, $amount->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_withOptionalAccumulatedDepreciation(): void
    {
        // Act
        $amount = new DepreciationAmount(100.0, 'USD');

        // Assert
        $this->assertEquals(100.0, $amount->amount);
        $this->assertEquals('USD', $amount->currency);
        $this->assertNull($amount->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function add_twoAmounts_returnsCorrectSum(): void
    {
        // Arrange
        $amount1 = new DepreciationAmount(100.0, 'USD', 500.0);
        $amount2 = new DepreciationAmount(50.0, 'USD', 550.0);

        // Act
        $result = $amount1->add($amount2);

        // Assert
        $this->assertEquals(150.0, $result->amount);
        // The add method adds both accumulated values plus the second amount
        $this->assertEquals(1100.0, $result->accumulatedDepreciation);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function add_differentCurrencies_throwsException(): void
    {
        // Arrange
        $amount1 = new DepreciationAmount(100.0, 'USD');
        $amount2 = new DepreciationAmount(50.0, 'EUR');

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add depreciation amounts with different currencies');

        // Act
        $amount1->add($amount2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function subtract_twoAmounts_returnsCorrectDifference(): void
    {
        // Arrange
        $amount1 = new DepreciationAmount(100.0, 'USD', 500.0);
        $amount2 = new DepreciationAmount(30.0, 'USD', 530.0);

        // Act
        $result = $amount1->subtract($amount2);

        // Assert
        $this->assertEquals(70.0, $result->amount);
        $this->assertEquals(-30.0, $result->accumulatedDepreciation);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function subtract_differentCurrencies_throwsException(): void
    {
        // Arrange
        $amount1 = new DepreciationAmount(100.0, 'USD');
        $amount2 = new DepreciationAmount(30.0, 'EUR');

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot subtract depreciation amounts with different currencies');

        // Act
        $amount1->subtract($amount2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiply_withFactor_returnsScaledAmount(): void
    {
        // Arrange
        $amount = new DepreciationAmount(100.0, 'USD', 500.0);

        // Act
        $result = $amount->multiply(1.5);

        // Assert
        $this->assertEquals(150.0, $result->amount);
        $this->assertEquals(750.0, $result->accumulatedDepreciation);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiply_withZeroFactor_returnsZero(): void
    {
        // Arrange
        $amount = new DepreciationAmount(100.0, 'USD', 500.0);

        // Act
        $result = $amount->multiply(0.0);

        // Assert
        $this->assertEquals(0.0, $result->amount);
        $this->assertEquals(0.0, $result->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiply_withoutAccumulatedDepreciation_worksCorrectly(): void
    {
        // Arrange
        $amount = new DepreciationAmount(100.0, 'USD');

        // Act
        $result = $amount->multiply(2.0);

        // Assert
        $this->assertEquals(200.0, $result->amount);
        $this->assertNull($result->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAmount_returnsAmountValue(): void
    {
        // Arrange
        $amount = new DepreciationAmount(123.45, 'USD');

        // Act
        $result = $amount->getAmount();

        // Assert
        $this->assertEquals(123.45, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function format_withUsdCurrency_returnsFormattedString(): void
    {
        // Arrange
        $amount = new DepreciationAmount(1234.56, 'USD');

        // Act
        $result = $amount->format();

        // Assert
        $this->assertEquals('1,234.56 USD', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function format_withEurCurrency_returnsFormattedString(): void
    {
        // Arrange
        $amount = new DepreciationAmount(999.99, 'EUR');

        // Act
        $result = $amount->format();

        // Assert
        $this->assertEquals('999.99 EUR', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function format_withZeroAmount_returnsFormattedString(): void
    {
        // Arrange
        $amount = new DepreciationAmount(0.0, 'USD');

        // Act
        $result = $amount->format();

        // Assert
        $this->assertEquals('0.00 USD', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function add_multipleTimes_chainsCorrectly(): void
    {
        // Arrange
        $amount1 = new DepreciationAmount(100.0, 'USD', 0.0);
        $amount2 = new DepreciationAmount(50.0, 'USD', 100.0);
        $amount3 = new DepreciationAmount(25.0, 'USD', 150.0);

        // Act
        $result = $amount1->add($amount2)->add($amount3);

        // Assert
        $this->assertEquals(175.0, $result->amount);
        $this->assertEquals(325.0, $result->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function subtract_resultsInNegativeAccumulatedDepreciation(): void
    {
        // Arrange
        $amount1 = new DepreciationAmount(50.0, 'USD', 500.0);
        $amount2 = new DepreciationAmount(100.0, 'USD', 600.0);

        // Act
        $result = $amount1->subtract($amount2);

        // Assert
        $this->assertEquals(-50.0, $result->amount);
        $this->assertEquals(-100.0, $result->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isImmutable_addCreatesNewInstance(): void
    {
        // Arrange
        $original = new DepreciationAmount(100.0, 'USD', 500.0);
        $toAdd = new DepreciationAmount(50.0, 'USD', 550.0);

        // Act
        $result = $original->add($toAdd);

        // Assert
        $this->assertNotSame($original, $result);
        $this->assertEquals(100.0, $original->amount);
        $this->assertEquals(500.0, $original->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isImmutable_subtractCreatesNewInstance(): void
    {
        // Arrange
        $original = new DepreciationAmount(100.0, 'USD', 500.0);
        $toSubtract = new DepreciationAmount(30.0, 'USD', 530.0);

        // Act
        $result = $original->subtract($toSubtract);

        // Assert
        $this->assertNotSame($original, $result);
        $this->assertEquals(100.0, $original->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isImmutable_multiplyCreatesNewInstance(): void
    {
        // Arrange
        $original = new DepreciationAmount(100.0, 'USD', 500.0);

        // Act
        $result = $original->multiply(2.0);

        // Assert
        $this->assertNotSame($original, $result);
        $this->assertEquals(100.0, $original->amount);
    }
}
