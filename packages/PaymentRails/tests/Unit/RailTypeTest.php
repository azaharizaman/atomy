<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\RailType;
use PHPUnit\Framework\TestCase;

final class RailTypeTest extends TestCase
{
    public function test_label_is_defined_for_all_rails(): void
    {
        foreach (RailType::cases() as $rail) {
            self::assertNotSame('', $rail->label());
        }
    }

    public function test_capabilities_and_timings(): void
    {
        foreach (RailType::cases() as $rail) {
            self::assertTrue($rail->supportsCredit());
            self::assertIsInt($rail->typicalSettlementDays());
        }

        self::assertTrue(RailType::ACH->supportsDebit());
        self::assertFalse(RailType::WIRE->supportsDebit());

        self::assertTrue(RailType::WIRE->isRealTime());
        self::assertTrue(RailType::RTGS->isRealTime());
        self::assertFalse(RailType::ACH->isRealTime());

        self::assertSame(2, RailType::ACH->typicalSettlementDays());
        self::assertSame(0, RailType::BOOK_TRANSFER->typicalSettlementDays());

        self::assertTrue(RailType::ACH->requiresFileGeneration());
        self::assertTrue(RailType::CHECK->requiresFileGeneration());
        self::assertFalse(RailType::WIRE->requiresFileGeneration());
    }
}
