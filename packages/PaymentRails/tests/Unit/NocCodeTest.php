<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\NocCode;
use PHPUnit\Framework\TestCase;

final class NocCodeTest extends TestCase
{
    public function test_description_is_defined_for_all_codes(): void
    {
        foreach (NocCode::cases() as $code) {
            self::assertNotSame('', $code->description());
        }
    }

    public function test_fieldsToUpdate_is_defined_for_all_codes(): void
    {
        foreach (NocCode::cases() as $code) {
            $fields = $code->fieldsToUpdate();
            self::assertNotEmpty($fields);
            foreach ($fields as $field) {
                self::assertIsString($field);
                self::assertNotSame('', $field);
            }
        }
    }

    public function test_affectsAccountInfo_and_company_and_iat_specific_flags(): void
    {
        self::assertTrue(NocCode::C01->affectsAccountInfo());
        self::assertTrue(NocCode::C07->affectsAccountInfo());
        self::assertFalse(NocCode::C10->affectsAccountInfo());

        self::assertTrue(NocCode::C10->affectsCompanyInfo());
        self::assertTrue(NocCode::C12->affectsCompanyInfo());
        self::assertFalse(NocCode::C01->affectsCompanyInfo());

        self::assertTrue(NocCode::C08->isIatSpecific());
        self::assertTrue(NocCode::C14->isIatSpecific());
        self::assertFalse(NocCode::C03->isIatSpecific());
    }
}
