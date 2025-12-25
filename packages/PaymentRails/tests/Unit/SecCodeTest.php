<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\SecCode;
use PHPUnit\Framework\TestCase;

final class SecCodeTest extends TestCase
{
    public function test_description_is_defined_for_all_codes(): void
    {
        foreach (SecCode::cases() as $sec) {
            self::assertNotSame('', $sec->description());
        }
    }

    public function test_consumer_and_corporate_classification(): void
    {
        self::assertTrue(SecCode::PPD->isConsumer());
        self::assertTrue(SecCode::WEB->isConsumer());
        self::assertFalse(SecCode::CCD->isConsumer());

        self::assertTrue(SecCode::CCD->isCorporate());
        self::assertTrue(SecCode::CTX->isCorporate());
        self::assertFalse(SecCode::PPD->isCorporate());
    }

    public function test_addenda_and_authorization_rules(): void
    {
        self::assertTrue(SecCode::CCD->supportsAddenda());
        self::assertTrue(SecCode::IAT->supportsAddenda());
        self::assertFalse(SecCode::TEL->supportsAddenda());

        self::assertTrue(SecCode::PPD->requiresWrittenAuth());
        self::assertTrue(SecCode::CCD->requiresWrittenAuth());
        self::assertFalse(SecCode::WEB->requiresWrittenAuth());
    }

    public function test_debit_support_is_universal_and_credit_support_is_limited(): void
    {
        foreach (SecCode::cases() as $sec) {
            self::assertTrue($sec->supportsDebit());
        }

        self::assertTrue(SecCode::PPD->supportsCredit());
        self::assertTrue(SecCode::IAT->supportsCredit());
        self::assertFalse(SecCode::WEB->supportsCredit());
        self::assertFalse(SecCode::RCK->supportsCredit());
    }
}
