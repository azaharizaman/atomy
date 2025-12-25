<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\WireType;
use PHPUnit\Framework\TestCase;

final class WireTypeTest extends TestCase
{
    public function test_label_is_defined_for_all_wire_types(): void
    {
        foreach (WireType::cases() as $type) {
            self::assertNotSame('', $type->label());
        }
    }

    public function test_swift_iban_intermediary_and_processing_characteristics(): void
    {
        self::assertTrue(WireType::INTERNATIONAL->requiresSwiftCode());
        self::assertFalse(WireType::DOMESTIC->requiresSwiftCode());

        self::assertTrue(WireType::INTERNATIONAL->mayRequireIban());
        self::assertFalse(WireType::BOOK_TRANSFER->mayRequireIban());

        self::assertTrue(WireType::INTERNATIONAL->mayRequireIntermediaryBank());
        self::assertFalse(WireType::DRAWDOWN->mayRequireIntermediaryBank());

        self::assertTrue(WireType::DOMESTIC->isSameDay());
        self::assertTrue(WireType::BOOK_TRANSFER->isSameDay());
        self::assertFalse(WireType::INTERNATIONAL->isSameDay());

        self::assertNotSame('', WireType::DOMESTIC->processingTime());
        self::assertNotSame('', WireType::INTERNATIONAL->processingTime());

        self::assertSame('MT103', WireType::INTERNATIONAL->swiftMessageType());
        self::assertNull(WireType::DOMESTIC->swiftMessageType());
    }
}
