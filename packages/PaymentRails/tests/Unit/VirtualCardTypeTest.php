<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\VirtualCardType;
use PHPUnit\Framework\TestCase;

final class VirtualCardTypeTest extends TestCase
{
    public function test_label_and_use_case_are_defined_for_all_types(): void
    {
        foreach (VirtualCardType::cases() as $type) {
            self::assertNotSame('', $type->label());
            self::assertNotSame('', $type->useCase());
        }
    }

    public function test_capability_flags(): void
    {
        self::assertFalse(VirtualCardType::SINGLE_USE->supportsMultipleTransactions());
        self::assertTrue(VirtualCardType::MULTI_USE->supportsMultipleTransactions());
        self::assertTrue(VirtualCardType::SUPPLIER_LOCKED->supportsMultipleTransactions());
        self::assertTrue(VirtualCardType::SUBSCRIPTION->supportsMultipleTransactions());

        self::assertTrue(VirtualCardType::SUPPLIER_LOCKED->isMerchantRestricted());
        self::assertTrue(VirtualCardType::SUBSCRIPTION->isMerchantRestricted());
        self::assertFalse(VirtualCardType::SINGLE_USE->isMerchantRestricted());
        self::assertFalse(VirtualCardType::MULTI_USE->isMerchantRestricted());
    }
}
