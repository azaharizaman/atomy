<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class PaymentMethodTest extends TestCase
{
    public function test_all_payment_methods_have_valid_values(): void
    {
        $expected = ['ACH', 'WIRE', 'CHECK', 'VIRTUAL_CARD', 'CREDIT_CARD'];
        $actual = array_map(fn(PaymentMethod $m) => $m->value, PaymentMethod::cases());

        $this->assertSame($expected, $actual);
    }

    public function test_processing_days(): void
    {
        $this->assertSame(2, PaymentMethod::ACH->getProcessingDays());
        $this->assertSame(0, PaymentMethod::WIRE->getProcessingDays());
        $this->assertSame(5, PaymentMethod::CHECK->getProcessingDays());
        $this->assertSame(0, PaymentMethod::VIRTUAL_CARD->getProcessingDays());
        $this->assertSame(1, PaymentMethod::CREDIT_CARD->getProcessingDays());
    }

    public function test_typical_fee_cents(): void
    {
        $this->assertSame(50, PaymentMethod::ACH->getTypicalFeeCents());
        $this->assertSame(2500, PaymentMethod::WIRE->getTypicalFeeCents());
        $this->assertSame(150, PaymentMethod::CHECK->getTypicalFeeCents());
        $this->assertSame(0, PaymentMethod::VIRTUAL_CARD->getTypicalFeeCents());
        $this->assertSame(0, PaymentMethod::CREDIT_CARD->getTypicalFeeCents());
    }

    public function test_supports_international(): void
    {
        $this->assertFalse(PaymentMethod::ACH->supportsInternational());
        $this->assertTrue(PaymentMethod::WIRE->supportsInternational());
        $this->assertFalse(PaymentMethod::CHECK->supportsInternational());
        $this->assertFalse(PaymentMethod::VIRTUAL_CARD->supportsInternational());
        $this->assertTrue(PaymentMethod::CREDIT_CARD->supportsInternational());
    }

    public function test_supports_same_day(): void
    {
        $this->assertFalse(PaymentMethod::ACH->supportsSameDay());
        $this->assertTrue(PaymentMethod::WIRE->supportsSameDay());
        $this->assertFalse(PaymentMethod::CHECK->supportsSameDay());
        $this->assertTrue(PaymentMethod::VIRTUAL_CARD->supportsSameDay());
        $this->assertTrue(PaymentMethod::CREDIT_CARD->supportsSameDay());
    }

    public function test_requires_bank_details(): void
    {
        $this->assertTrue(PaymentMethod::ACH->requiresBankDetails());
        $this->assertTrue(PaymentMethod::WIRE->requiresBankDetails());
        $this->assertFalse(PaymentMethod::CHECK->requiresBankDetails());
        $this->assertFalse(PaymentMethod::VIRTUAL_CARD->requiresBankDetails());
        $this->assertFalse(PaymentMethod::CREDIT_CARD->requiresBankDetails());
    }

    public function test_get_description(): void
    {
        $this->assertSame('ACH (Automated Clearing House)', PaymentMethod::ACH->getDescription());
        $this->assertSame('Wire Transfer', PaymentMethod::WIRE->getDescription());
        $this->assertSame('Paper Check', PaymentMethod::CHECK->getDescription());
        $this->assertSame('Virtual Card', PaymentMethod::VIRTUAL_CARD->getDescription());
        $this->assertSame('Credit Card', PaymentMethod::CREDIT_CARD->getDescription());
    }

    public function test_calculate_clearing_date(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-15');

        // ACH: 2 business days
        $achClearing = PaymentMethod::ACH->calculateClearingDate($startDate);
        $this->assertSame('2024-01-17', $achClearing->format('Y-m-d'));

        // Wire: Same day
        $wireClearing = PaymentMethod::WIRE->calculateClearingDate($startDate);
        $this->assertSame('2024-01-15', $wireClearing->format('Y-m-d'));

        // Check: 5 business days
        $checkClearing = PaymentMethod::CHECK->calculateClearingDate($startDate);
        // 15 + 5 days = 20th (Mon), but we need to account for weekends
        // 15 Mon + 5 = 20 Sat → 22 Mon
        // Actually: 15 Mon, 16 Tue, 17 Wed, 18 Thu, 19 Fri = 5 days = Jan 20
        $this->assertSame('2024-01-20', $checkClearing->format('Y-m-d'));
    }

    public function test_calculate_clearing_date_skips_weekends(): void
    {
        // Friday
        $friday = new \DateTimeImmutable('2024-01-19');

        // ACH: 2 days from Friday = next Tuesday (skips Sat/Sun)
        $achClearing = PaymentMethod::ACH->calculateClearingDate($friday);
        $this->assertSame('2024-01-23', $achClearing->format('Y-m-d'));
    }
}
