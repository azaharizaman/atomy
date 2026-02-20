<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\ValueObjects;

use Nexus\CostAccounting\ValueObjects\CostAmount;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CostAmount Value Object
 * 
 * @covers \Nexus\CostAccounting\ValueObjects\CostAmount
 */
final class CostAmountTest extends TestCase
{
    public function testCreateWithDefaultCurrency(): void
    {
        $cost = new CostAmount(100.00);
        
        self::assertSame(100.00, $cost->getAmount());
        self::assertSame('USD', $cost->getCurrency());
    }

    public function testCreateWithCustomCurrency(): void
    {
        $cost = new CostAmount(100.00, 'EUR');
        
        self::assertSame(100.00, $cost->getAmount());
        self::assertSame('EUR', $cost->getCurrency());
    }

    public function testCreateWithZero(): void
    {
        $cost = new CostAmount(0.0);
        
        self::assertSame(0.0, $cost->getAmount());
    }

    public function testNegativeAmountThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cost amount cannot be negative');
        
        new CostAmount(-100.00);
    }

    public function testAddSameCurrency(): void
    {
        $cost1 = new CostAmount(100.00);
        $cost2 = new CostAmount(50.00);
        
        $result = $cost1->add($cost2);
        
        self::assertSame(150.00, $result->getAmount());
        self::assertSame('USD', $result->getCurrency());
    }

    public function testAddDifferentCurrencyThrowsException(): void
    {
        $cost1 = new CostAmount(100.00, 'USD');
        $cost2 = new CostAmount(50.00, 'EUR');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');
        
        $cost1->add($cost2);
    }

    public function testSubtractSameCurrency(): void
    {
        $cost1 = new CostAmount(100.00);
        $cost2 = new CostAmount(30.00);
        
        $result = $cost1->subtract($cost2);
        
        self::assertSame(70.00, $result->getAmount());
    }

    public function testSubtractDifferentCurrencyThrowsException(): void
    {
        $cost1 = new CostAmount(100.00, 'USD');
        $cost2 = new CostAmount(30.00, 'GBP');
        
        $this->expectException(\InvalidArgumentException::class);
        
        $cost1->subtract($cost2);
    }

    public function testMultiply(): void
    {
        $cost = new CostAmount(100.00);
        
        $result = $cost->multiply(1.5);
        
        self::assertSame(150.00, $result->getAmount());
    }

    public function testMultiplyByZero(): void
    {
        $cost = new CostAmount(100.00);
        
        $result = $cost->multiply(0);
        
        self::assertSame(0.0, $result->getAmount());
    }

    public function testDivide(): void
    {
        $cost = new CostAmount(100.00);
        
        $result = $cost->divide(4);
        
        self::assertSame(25.00, $result->getAmount());
    }

    public function testDivideByZeroThrowsException(): void
    {
        $cost = new CostAmount(100.00);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero');
        
        $cost->divide(0.0);
    }

    public function testIsGreaterThanReturnsTrue(): void
    {
        $cost1 = new CostAmount(100.00);
        $cost2 = new CostAmount(50.00);
        
        self::assertTrue($cost1->isGreaterThan($cost2));
    }

    public function testIsGreaterThanReturnsFalseWhenLess(): void
    {
        $cost1 = new CostAmount(50.00);
        $cost2 = new CostAmount(100.00);
        
        self::assertFalse($cost1->isGreaterThan($cost2));
    }

    public function testIsGreaterThanThrowsOnCurrencyMismatch(): void
    {
        $cost1 = new CostAmount(100.00, 'USD');
        $cost2 = new CostAmount(50.00, 'EUR');
        
        $this->expectException(\InvalidArgumentException::class);
        
        $cost1->isGreaterThan($cost2);
    }

    public function testIsLessThanReturnsTrue(): void
    {
        $cost1 = new CostAmount(50.00);
        $cost2 = new CostAmount(100.00);
        
        self::assertTrue($cost1->isLessThan($cost2));
    }

    public function testIsLessThanReturnsFalseWhenGreater(): void
    {
        $cost1 = new CostAmount(100.00);
        $cost2 = new CostAmount(50.00);
        
        self::assertFalse($cost1->isLessThan($cost2));
    }

    public function testIsEqualToReturnsTrue(): void
    {
        $cost1 = new CostAmount(100.00);
        $cost2 = new CostAmount(100.00);
        
        self::assertTrue($cost1->isEqualTo($cost2));
    }

    public function testIsEqualToReturnsFalse(): void
    {
        $cost1 = new CostAmount(100.00);
        $cost2 = new CostAmount(100.01);
        
        self::assertFalse($cost1->isEqualTo($cost2));
    }

    public function testToString(): void
    {
        $cost = new CostAmount(123.45);
        
        self::assertSame('USD 123.45', (string) $cost);
    }
}
