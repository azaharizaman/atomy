<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\RtgsSystem;
use PHPUnit\Framework\TestCase;

final class RtgsSystemTest extends TestCase
{
    public function test_labels_regions_currencies_and_operating_hours_are_defined(): void
    {
        foreach (RtgsSystem::cases() as $system) {
            self::assertNotSame('', $system->label());
            self::assertNotSame('', $system->region());
            self::assertNotSame('', $system->currency());
            self::assertNotSame('', $system->operatingHours());
        }
    }

    public function test_system_specific_flags_and_thresholds(): void
    {
        self::assertTrue(RtgsSystem::CHIPS->isNetSettlement());
        self::assertFalse(RtgsSystem::FEDWIRE->isNetSettlement());

        self::assertSame(200000, RtgsSystem::RTGS_I->minimumValue());
        self::assertNull(RtgsSystem::FEDWIRE->minimumValue());
    }

    public function test_forCountry_maps_country_codes_to_expected_systems(): void
    {
        self::assertSame(RtgsSystem::FEDWIRE, RtgsSystem::forCountry('us'));
        self::assertSame(RtgsSystem::CHAPS, RtgsSystem::forCountry('GB'));
        self::assertSame(RtgsSystem::CHAPS, RtgsSystem::forCountry('UK'));
        self::assertSame(RtgsSystem::TARGET2, RtgsSystem::forCountry('DE'));
        self::assertSame(RtgsSystem::RENTAS, RtgsSystem::forCountry('MY'));
        self::assertNull(RtgsSystem::forCountry('ZZ'));
    }
}
