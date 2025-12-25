<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Exceptions\InvalidSwiftCodeException;
use Nexus\PaymentRails\ValueObjects\SwiftCode;
use PHPUnit\Framework\TestCase;

final class SwiftCodeTest extends TestCase
{
    public function test_construct_normalizes_uppercase_and_parses_parts_for_8_char_code(): void
    {
        $swift = new SwiftCode('deutdeff');

        self::assertSame('DEUTDEFF', $swift->value);
        self::assertSame('DEUT', $swift->getBankCode());
        self::assertSame('DE', $swift->getCountryCode());
        self::assertSame('FF', $swift->getLocationCode());
        self::assertNull($swift->getBranchCode());
        self::assertTrue($swift->isPrimaryOffice());
        self::assertSame('DEUT DEFF', $swift->formatted());
    }

    public function test_construct_parses_parts_for_11_char_code(): void
    {
        $swift = new SwiftCode('DEUTDEFF500');

        self::assertSame('DEUTDEFF500', $swift->value);
        self::assertSame('500', $swift->getBranchCode());
        self::assertFalse($swift->isPrimaryOffice());
        self::assertSame('DEUT DEFF 500', $swift->formatted());
    }

    public function test_tryFromString_returns_null_for_invalid_format(): void
    {
        self::assertNull(SwiftCode::tryFromString('1234'));
        self::assertNull(SwiftCode::tryFromString('DEUTDEFF5')); // 9 chars invalid
    }

    public function test_invalid_format_throws_exception(): void
    {
        $this->expectException(InvalidSwiftCodeException::class);

        new SwiftCode('DEUT-DEFF');
    }

    public function test_toPrimaryOffice_truncates_11_char_code(): void
    {
        $swift = new SwiftCode('DEUTDEFF500');
        $primary = $swift->toPrimaryOffice();

        self::assertSame('DEUTDEFF', $primary->value);
        self::assertTrue($primary->isPrimaryOffice());
    }

    public function test_toFullFormat_appends_xxx_for_8_char_code(): void
    {
        $swift = new SwiftCode('DEUTDEFF');
        $full = $swift->toFullFormat();

        self::assertSame('DEUTDEFFXXX', $full->value);
        self::assertTrue($full->isPrimaryOffice());
    }

    public function test_isTestCode_and_isPassiveParticipant_are_based_on_location_second_character(): void
    {
        $test = new SwiftCode('DEUTDEF0');
        self::assertTrue($test->isTestCode());
        self::assertFalse($test->isPassiveParticipant());

        $passive = new SwiftCode('DEUTDEF1');
        self::assertFalse($passive->isTestCode());
        self::assertTrue($passive->isPassiveParticipant());
    }

    public function test_isSameBank_compares_bank_and_country_codes(): void
    {
        $a = new SwiftCode('DEUTDEFF');
        $b = new SwiftCode('DEUTDEFF500');
        $c = new SwiftCode('NEDSZAJJ');

        self::assertTrue($a->isSameBank($b));
        self::assertFalse($a->isSameBank($c));
    }

    public function test_equals_compares_value(): void
    {
        $a = new SwiftCode('DEUTDEFF');
        $b = new SwiftCode('DEUTDEFF');
        $c = new SwiftCode('DEUTDEFF500');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function test_fromString_creates_instance(): void
    {
        $swift = SwiftCode::fromString('DEUTDEFF');
        self::assertSame('DEUTDEFF', $swift->value);
    }

    public function test_tryFromString_returns_instance_on_valid_input(): void
    {
        $swift = SwiftCode::tryFromString('DEUTDEFF');
        self::assertNotNull($swift);
        self::assertSame('DEUTDEFF', $swift->value);
    }

    public function test_toString_returns_value(): void
    {
        $swift = new SwiftCode('DEUTDEFF');
        self::assertSame('DEUTDEFF', $swift->toString());
        self::assertSame('DEUTDEFF', (string) $swift);
    }

    public function test_toPrimaryOffice_returns_self_if_already_8_chars(): void
    {
        $swift = new SwiftCode('DEUTDEFF');
        self::assertSame($swift, $swift->toPrimaryOffice());
    }

    public function test_toFullFormat_returns_self_if_already_11_chars(): void
    {
        $swift = new SwiftCode('DEUTDEFFXXX');
        self::assertSame($swift, $swift->toFullFormat());
    }
}
