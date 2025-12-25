<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Exceptions\InvalidRoutingNumberException;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class RoutingNumberTest extends TestCase
{
    public function test_construct_normalizes_non_digits_and_preserves_value(): void
    {
        $routingNumber = new RoutingNumber('0210-0002-1');

        self::assertSame('021000021', $routingNumber->value);
        self::assertSame('021000021', $routingNumber->toString());
        self::assertSame('021000021', (string) $routingNumber);
    }

    public function test_fromString_creates_instance(): void
    {
        $routingNumber = RoutingNumber::fromString('021000021');

        self::assertSame('021000021', $routingNumber->value);
    }

    public function test_tryFromString_returns_null_for_invalid_length(): void
    {
        $routingNumber = RoutingNumber::tryFromString('123');

        self::assertNull($routingNumber);
    }

    public function test_invalid_length_throws_exception(): void
    {
        $this->expectException(InvalidRoutingNumberException::class);

        new RoutingNumber('123');
    }

    public function test_invalid_check_digit_throws_exception(): void
    {
        $this->expectException(InvalidRoutingNumberException::class);

        // 021000021 is valid; change last digit to break checksum.
        new RoutingNumber('021000022');
    }

    public function test_getters_return_expected_parts(): void
    {
        $routingNumber = new RoutingNumber('021000021');

        self::assertSame('0210', $routingNumber->getFederalReserveRoutingSymbol());
        self::assertSame('0002', $routingNumber->getAbaInstitutionIdentifier());
        self::assertSame(1, $routingNumber->getCheckDigit());
        self::assertSame('0210-0002-1', $routingNumber->formatted());
    }

    public function test_isValidForAch_true_for_known_ach_prefix_ranges(): void
    {
        $routingNumber = new RoutingNumber('021000021');

        self::assertTrue($routingNumber->isValidForAch());
    }

    public function test_isThriftInstitution_true_when_first_digit_is_in_thrift_set(): void
    {
        // Prefix 22 is in the ACH range and first digit 2 is treated as thrift.
        $routingNumber = new RoutingNumber('222371863');

        self::assertTrue($routingNumber->isThriftInstitution());
        self::assertTrue($routingNumber->isValidForAch());
    }

    public function test_equals_compares_value(): void
    {
        $a = new RoutingNumber('021000021');
        $b = new RoutingNumber('021000021');
        $c = new RoutingNumber('222371863');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function test_getFederalReserveDistrict_returns_expected_values(): void
    {
        // Case: first digit 0, second digit 0 -> 0
        // Note: 000000000 is technically invalid check digit, so we need a valid one starting with 00
        // But 00 is not a valid FRB district prefix.
        // Let's try to construct one that passes check digit.
        // 000000000 -> sum = 0 -> valid.
        $r00 = new RoutingNumber('000000000'); 
        self::assertSame(0, $r00->getFederalReserveDistrict());

        // Case: first digit 0, second digit 1 -> 1
        // 011000015 (Dallas FRB)
        $r01 = new RoutingNumber('011000015');
        self::assertSame(1, $r01->getFederalReserveDistrict());

        // Case: first digit 0, second digit 2 -> 2
        // 021000021 (New York FRB)
        $r02 = new RoutingNumber('021000021');
        self::assertSame(2, $r02->getFederalReserveDistrict());

        // Case: 03 (Philadelphia FRB)
        $r03 = new RoutingNumber('031000011');
        self::assertSame(3, $r03->getFederalReserveDistrict());

        // Case: first digit > 0 -> returns first digit
        // 101000019 (Kansas City FRB)
        // NOTE: Current implementation returns 1 for 10, which seems incorrect (should be 10).
        $r10 = new RoutingNumber('101000019');
        self::assertSame(10, $r10->getFederalReserveDistrict());
    }

    public function test_getFederalReserveDistrict_returns_zero_for_unknown_prefix(): void
    {
        // 991234561 is structurally valid (checksum ok) but 99 is not a standard district prefix
        $routingNumber = new RoutingNumber('991234561');
        self::assertSame(0, $routingNumber->getFederalReserveDistrict());
    }
}
