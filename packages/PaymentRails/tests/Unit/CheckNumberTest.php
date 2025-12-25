<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Exceptions\InvalidCheckNumberException;
use Nexus\PaymentRails\ValueObjects\CheckNumber;
use PHPUnit\Framework\TestCase;

final class CheckNumberTest extends TestCase
{
    public function test_construct_accepts_numeric_string_and_normalizes_to_int_value(): void
    {
        $check = new CheckNumber('000123');

        self::assertSame('123', $check->value);
        self::assertSame(123, $check->toInt());
        self::assertSame('000123', $check->formatted(6));
    }

    public function test_invalid_non_numeric_throws(): void
    {
        $this->expectException(InvalidCheckNumberException::class);

        new CheckNumber('12A3');
    }

    public function test_below_minimum_throws(): void
    {
        $this->expectException(InvalidCheckNumberException::class);

        new CheckNumber('0');
    }

    public function test_above_maximum_throws(): void
    {
        $this->expectException(InvalidCheckNumberException::class);

        new CheckNumber('1000000000');
    }

    public function test_next_increments_by_one(): void
    {
        $check = CheckNumber::fromInt(999);
        $next = $check->next();

        self::assertSame('1000', $next->value);
    }

    public function test_advance_moves_forward_by_n(): void
    {
        $check = CheckNumber::fromInt(1000);
        $advanced = $check->advance(5);

        self::assertSame('1005', $advanced->value);
    }

    public function test_isBefore_and_isAfter_order_numbers(): void
    {
        $a = CheckNumber::fromInt(10);
        $b = CheckNumber::fromInt(20);

        self::assertTrue($a->isBefore($b));
        self::assertFalse($a->isAfter($b));

        self::assertTrue($b->isAfter($a));
        self::assertFalse($b->isBefore($a));
    }

    public function test_equals_compares_value(): void
    {
        $a = CheckNumber::fromInt(123);
        $b = new CheckNumber('000123');
        $c = CheckNumber::fromInt(124);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function test_fromString_creates_instance(): void
    {
        $check = CheckNumber::fromString('12345');
        self::assertSame('12345', $check->value);
    }

    public function test_tryFromString_returns_instance_on_valid_input(): void
    {
        $check = CheckNumber::tryFromString('12345');
        self::assertNotNull($check);
        self::assertSame('12345', $check->value);
    }

    public function test_tryFromString_returns_null_on_invalid_input(): void
    {
        $check = CheckNumber::tryFromString('invalid');
        self::assertNull($check);
    }

    public function test_toString_returns_value(): void
    {
        $check = new CheckNumber('12345');
        self::assertSame('12345', $check->toString());
        self::assertSame('12345', (string) $check);
    }
}
