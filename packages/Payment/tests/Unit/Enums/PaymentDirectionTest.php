<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Enums;

use Nexus\Payment\Enums\PaymentDirection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentDirection::class)]
final class PaymentDirectionTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = PaymentDirection::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(PaymentDirection::INBOUND, $cases);
        $this->assertContains(PaymentDirection::OUTBOUND, $cases);
    }

    #[Test]
    #[DataProvider('directionLabelsProvider')]
    public function it_returns_correct_labels(PaymentDirection $direction, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $direction->label());
    }

    public static function directionLabelsProvider(): iterable
    {
        yield 'INBOUND' => [PaymentDirection::INBOUND, 'Inbound'];
        yield 'OUTBOUND' => [PaymentDirection::OUTBOUND, 'Outbound'];
    }

    #[Test]
    public function inbound_is_receipt(): void
    {
        $this->assertTrue(PaymentDirection::INBOUND->isInbound());
        $this->assertFalse(PaymentDirection::INBOUND->isOutbound());
    }

    #[Test]
    public function outbound_is_disbursement(): void
    {
        $this->assertTrue(PaymentDirection::OUTBOUND->isOutbound());
        $this->assertFalse(PaymentDirection::OUTBOUND->isInbound());
    }

    #[Test]
    public function inbound_has_positive_accounting_sign(): void
    {
        $this->assertSame(1, PaymentDirection::INBOUND->accountingSign());
    }

    #[Test]
    public function outbound_has_negative_accounting_sign(): void
    {
        $this->assertSame(-1, PaymentDirection::OUTBOUND->accountingSign());
    }

    #[Test]
    public function it_returns_opposite_direction(): void
    {
        $this->assertSame(PaymentDirection::OUTBOUND, PaymentDirection::INBOUND->opposite());
        $this->assertSame(PaymentDirection::INBOUND, PaymentDirection::OUTBOUND->opposite());
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $inbound = PaymentDirection::from('inbound');
        $outbound = PaymentDirection::from('outbound');

        $this->assertSame(PaymentDirection::INBOUND, $inbound);
        $this->assertSame(PaymentDirection::OUTBOUND, $outbound);
    }

    #[Test]
    public function it_returns_null_for_invalid_direction(): void
    {
        $direction = PaymentDirection::tryFrom('invalid');

        $this->assertNull($direction);
    }
}
