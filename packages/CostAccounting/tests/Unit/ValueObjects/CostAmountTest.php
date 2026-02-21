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
        $cost = CostAmount::fromFloat(100.00);
        
        self::assertSame(100.00, $cost->getAmount());
        self::assertSame(10000, $cost->getCents());
        self::assertSame('USD', $cost->getCurrency());
    }

    public function testCreateWithCustomCurrency(): void
    {
        $cost = CostAmount::fromFloat(100.00, 'EUR');
        
        self::assertSame(100.00, $cost->getAmount());
        self::assertSame('EUR', $cost->getCurrency());
    }

    public function testCreateWithZero(): void
    {
        $cost = CostAmount::fromFloat(0.0);
        
        self::assertSame(0.0, $cost->getAmount());
        self::assertSame(0, $cost->getCents());
    }

    public function testCreateWithFromCents(): void
    {
        $cost = CostAmount::fromCents(10050);
        
        self::assertSame(100.50, $cost->getAmount());
        self::assertSame(10050, $cost->getCents());
    }

    public function testNegativeAmountThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cost amount cannot be negative');
        
        CostAmount::fromFloat(-100.00);
    }

    public function testAddSameCurrency(): void
    {
        $cost1 = CostAmount::fromFloat(100.00);
        $cost2 = CostAmount::fromFloat(50.00);
        
        $result = $cost1->add($cost2);
        
        self::assertSame(150.00, $result->getAmount());
        self::assertSame('USD', $result->getCurrency());
    }

    public function testAddDifferentCurrencyThrowsException(): void
    {
        $cost1 = CostAmount::fromFloat(100.00, 'USD');
        $cost2 = CostAmount::fromFloat(50.00, 'EUR');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');
        
        $cost1->add($cost2);
    }

    public function testSubtractSameCurrency(): void
    {
        $cost1 = CostAmount::fromFloat(100.00);
        $cost2 = CostAmount::fromFloat(30.00);
        
        $result = $cost1->subtract($cost2);
        
        self::assertSame(70.00, $result->getAmount());
    }

    public function testSubtractThrowsWhenResultWouldBeNegative(): void
    {
        $cost1 = CostAmount::fromFloat(30.00);
        $cost2 = CostAmount::fromFloat(100.00);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Subtraction result would be negative');
        
        $cost1->subtract($cost2);
    }

    public function testSubtractDifferentCurrencyThrowsException(): void
    {
        $cost1 = CostAmount::fromFloat(100.00, 'USD');
        $cost2 = CostAmount::fromFloat(30.00, 'GBP');
        
        $this->expectException(\InvalidArgumentException::class);
        
        $cost1->subtract($cost2);
    }

    public function testMultiply(): void
    {
        $cost = CostAmount::fromFloat(100.00);
        
        $result = $cost->multiply(1.5);
        
        self::assertSame(150.00, $result->getAmount());
    }

    public function testMultiplyByZero(): void
    {
        $cost = CostAmount::fromFloat(100.00);
        
        $result = $cost->multiply(0);
        
        self::assertSame(0.0, $result->getAmount());
    }

    public function testDivide(): void
    {
        $cost = CostAmount::fromFloat(100.00);
        
        $result = $cost->divide(4);
        
        self::assertSame(25.00, $result->getAmount());
    }

    public function testDivideByZeroThrowsException(): void
    {
        $cost = CostAmount::fromFloat(100.00);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero');
        
        $cost->divide(0.0);
    }

    public function testIsGreaterThanReturnsTrue(): void
    {
        $cost1 = CostAmount::fromFloat(100.00);
        $cost2 = CostAmount::fromFloat(50.00);
        
        self::assertTrue($cost1->isGreaterThan($cost2));
    }

    public function testIsGreaterThanReturnsFalseWhenLess(): void
    {
        $cost1 = CostAmount::fromFloat(50.00);
        $cost2 = CostAmount::fromFloat(100.00);
        
        self::assertFalse($cost1->isGreaterThan($cost2));
    }

    public function testIsGreaterThanThrowsOnCurrencyMismatch(): void
    {
        $cost1 = CostAmount::fromFloat(100.00, 'USD');
        $cost2 = CostAmount::fromFloat(50.00, 'EUR');
        
        $this->expectException(\InvalidArgumentException::class);
        
        $cost1->isGreaterThan($cost2);
    }

    public function testIsLessThanReturnsTrue(): void
    {
        $cost1 = CostAmount::fromFloat(50.00);
        $cost2 = CostAmount::fromFloat(100.00);
        
        self::assertTrue($cost1->isLessThan($cost2));
    }

    public function testIsLessThanReturnsFalseWhenGreater(): void
    {
        $cost1 = CostAmount::fromFloat(100.00);
        $cost2 = CostAmount::fromFloat(50.00);
        
        self::assertFalse($cost1->isLessThan($cost2));
    }

    public function testIsEqualToReturnsTrue(): void
    {
        $cost1 = CostAmount::fromFloat(100.00);
        $cost2 = CostAmount::fromFloat(100.00);
        
        self::assertTrue($cost1->isEqualTo($cost2));
    }

    public function testIsEqualToReturnsFalse(): void
    {
        $cost1 = CostAmount::fromFloat(100.00);
        $cost2 = CostAmount::fromFloat(100.01);
        
        self::assertFalse($cost1->isEqualTo($cost2));
    }

    public function testToString(): void
    {
        $cost = CostAmount::fromFloat(123.45);
        
        self::assertSame('USD 123.45', (string) $cost);
    }

    public function testPrecisionWithRounding(): void
    {
        $cost = CostAmount::fromFloat(0.345);
        
        self::assertSame(0.35, $cost->getAmount());
    }
}
