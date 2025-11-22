<?php

declare(strict_types=1);

namespace Nexus\Finance\Tests\Unit\ValueObjects;

use InvalidArgumentException;
use Nexus\Finance\ValueObjects\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Money::class)]
final class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_money_from_numeric_value(): void
    {
        $money = Money::of(100.50, 'MYR');

        $this->assertSame('100.5000', $money->getAmount());
        $this->assertSame('MYR', $money->getCurrency());
        $this->assertSame(100.5, $money->toFloat());
    }

    #[Test]
    public function it_creates_zero_money(): void
    {
        $money = Money::zero('USD');

        $this->assertSame('0.0000', $money->getAmount());
        $this->assertSame('USD', $money->getCurrency());
    }

    #[Test]
    public function it_maintains_4_decimal_precision(): void
    {
        $money = Money::of(99.123456, 'MYR');

        $this->assertSame('99.1235', $money->getAmount());
    }

    #[Test]
    public function it_adds_money_with_same_currency(): void
    {
        $money1 = Money::of(100, 'MYR');
        $money2 = Money::of(50.25, 'MYR');

        $result = $money1->add($money2);

        $this->assertSame('150.2500', $result->getAmount());
        $this->assertSame('MYR', $result->getCurrency());
    }

    #[Test]
    public function it_throws_exception_when_adding_different_currencies(): void
    {
        $money1 = Money::of(100, 'MYR');
        $money2 = Money::of(50, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot operate on different currencies');

        $money1->add($money2);
    }

    #[Test]
    public function it_subtracts_money_with_same_currency(): void
    {
        $money1 = Money::of(100, 'MYR');
        $money2 = Money::of(30.75, 'MYR');

        $result = $money1->subtract($money2);

        $this->assertSame('69.2500', $result->getAmount());
        $this->assertSame('MYR', $result->getCurrency());
    }

    #[Test]
    public function it_throws_exception_when_subtracting_different_currencies(): void
    {
        $money1 = Money::of(100, 'MYR');
        $money2 = Money::of(50, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot operate on different currencies');

        $money1->subtract($money2);
    }

    #[Test]
    public function it_allows_negative_amounts_after_subtraction(): void
    {
        $money1 = Money::of(50, 'MYR');
        $money2 = Money::of(100, 'MYR');

        $result = $money1->subtract($money2);

        $this->assertSame('-50.0000', $result->getAmount());
    }

    #[Test]
    public function it_multiplies_by_factor(): void
    {
        $money = Money::of(100, 'MYR');

        $result = $money->multiply(1.5);

        $this->assertSame('150.0000', $result->getAmount());
        $this->assertSame('MYR', $result->getCurrency());
    }

    #[Test]
    public function it_multiplies_by_integer(): void
    {
        $money = Money::of(25.50, 'MYR');

        $result = $money->multiply(3);

        $this->assertSame('76.5000', $result->getAmount());
    }

    #[Test]
    public function it_divides_by_factor(): void
    {
        $money = Money::of(100, 'MYR');

        $result = $money->divide(4);

        $this->assertSame('25.0000', $result->getAmount());
        $this->assertSame('MYR', $result->getCurrency());
    }

    #[Test]
    public function it_throws_exception_when_dividing_by_zero(): void
    {
        $money = Money::of(100, 'MYR');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero');

        $money->divide(0);
    }

    #[Test]
    public function it_maintains_precision_in_division(): void
    {
        $money = Money::of(100, 'MYR');

        $result = $money->divide(3);

        $this->assertSame('33.3333', $result->getAmount());
    }

    #[Test]
    #[DataProvider('invalidAmountProvider')]
    public function it_rejects_invalid_amounts(mixed $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money((string)$amount, 'MYR');
    }

    #[Test]
    #[DataProvider('invalidCurrencyProvider')]
    public function it_rejects_invalid_currencies(string $currency): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of(100, $currency);
    }

    #[Test]
    public function it_checks_if_zero(): void
    {
        $zero = Money::zero('MYR');
        $nonZero = Money::of(0.0001, 'MYR');

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    #[Test]
    public function it_checks_if_positive(): void
    {
        $positive = Money::of(100, 'MYR');
        $negative = Money::of(-100, 'MYR');
        $zero = Money::zero('MYR');

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($negative->isPositive());
        $this->assertFalse($zero->isPositive());
    }

    #[Test]
    public function it_checks_if_negative(): void
    {
        $positive = Money::of(100, 'MYR');
        $negative = Money::of(-100, 'MYR');
        $zero = Money::zero('MYR');

        $this->assertFalse($positive->isNegative());
        $this->assertTrue($negative->isNegative());
        $this->assertFalse($zero->isNegative());
    }

    #[Test]
    public function it_compares_money_amounts(): void
    {
        $money1 = Money::of(100, 'MYR');
        $money2 = Money::of(100, 'MYR');
        $money3 = Money::of(50, 'MYR');

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    #[Test]
    public function it_compares_greater_than(): void
    {
        $larger = Money::of(100, 'MYR');
        $smaller = Money::of(50, 'MYR');

        $this->assertTrue($larger->greaterThan($smaller));
        $this->assertFalse($smaller->greaterThan($larger));
    }

    #[Test]
    public function it_compares_less_than(): void
    {
        $larger = Money::of(100, 'MYR');
        $smaller = Money::of(50, 'MYR');

        $this->assertTrue($smaller->lessThan($larger));
        $this->assertFalse($larger->lessThan($smaller));
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $original = Money::of(100, 'MYR');
        $added = $original->add(Money::of(50, 'MYR'));

        $this->assertSame('100.0000', $original->getAmount());
        $this->assertSame('150.0000', $added->getAmount());
        $this->assertNotSame($original, $added);
    }

    #[Test]
    #[DataProvider('arithmeticEdgeCasesProvider')]
    public function it_handles_arithmetic_edge_cases(
        float $amount1,
        float $amount2,
        string $operation,
        string $expected
    ): void {
        $money1 = Money::of($amount1, 'MYR');
        $money2 = Money::of($amount2, 'MYR');

        $result = match ($operation) {
            'add' => $money1->add($money2),
            'subtract' => $money1->subtract($money2),
            default => throw new \InvalidArgumentException("Unknown operation: {$operation}")
        };

        $this->assertSame($expected, $result->getAmount());
    }

    public static function invalidAmountProvider(): array
    {
        return [
            'empty string' => [''],
            'non-numeric string' => ['abc'],
        ];
    }

    public static function invalidCurrencyProvider(): array
    {
        return [
            'empty string' => [''],
            'too short' => ['MY'],
            'too long' => ['MYRD'],
            'lowercase' => ['myr'],
            'numeric' => ['123'],
        ];
    }

    public static function arithmeticEdgeCasesProvider(): array
    {
        return [
            'very small amounts' => [0.0001, 0.0002, 'add', '0.0003'],
            'large amounts' => [999999.9999, 0.0001, 'add', '1000000.0000'],
            'negative + positive' => [-50.0000, 100.0000, 'add', '50.0000'],
            'negative + negative' => [-50.0000, -25.0000, 'add', '-75.0000'],
            'zero subtraction' => [100.0000, 0.0000, 'subtract', '100.0000'],
        ];
    }
}
