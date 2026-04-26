<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\DecimalString;
use Tests\TestCase;

final class DecimalStringTest extends TestCase
{
    public function testNormalizeReturnsNullForNonNumericInput(): void
    {
        self::assertNull(DecimalString::normalize('banana', 4));
        self::assertNull(DecimalString::normalize('   ', 2));
    }

    public function testNormalizeTrimsAndPadsNumericInput(): void
    {
        self::assertSame('12.3400', DecimalString::normalize(' 12.34 ', 4));
        self::assertSame('-5', DecimalString::normalize(' -0005 ', 0));
        self::assertSame('0', DecimalString::normalize('0', 3));
    }

    public function testNormalizeCanonicalizesZeroAndLeadingPlus(): void
    {
        self::assertSame('0', DecimalString::normalize('-0', 0));
        self::assertSame('0', DecimalString::normalize('-0.0001', 3));
        self::assertSame('5', DecimalString::normalize('+5', 0));
        self::assertSame('12.34', DecimalString::normalize('12.345', 2));
    }
}
