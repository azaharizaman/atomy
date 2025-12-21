<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\TransactionStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransactionStatus::class)]
final class TransactionStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_statuses(): void
    {
        $expectedStatuses = [
            'PENDING',
            'AUTHORIZED',
            'CAPTURED',
            'PARTIALLY_CAPTURED',
            'VOIDED',
            'REFUNDED',
            'PARTIALLY_REFUNDED',
            'FAILED',
            'DECLINED',
            'EXPIRED',
        ];

        $actualStatuses = array_map(
            fn (TransactionStatus $status) => $status->name,
            TransactionStatus::cases()
        );

        $this->assertSame($expectedStatuses, $actualStatuses);
    }

    #[Test]
    #[DataProvider('statusLabelProvider')]
    public function it_returns_correct_labels(TransactionStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    public static function statusLabelProvider(): array
    {
        return [
            'pending' => [TransactionStatus::PENDING, 'Pending'],
            'authorized' => [TransactionStatus::AUTHORIZED, 'Authorized'],
            'captured' => [TransactionStatus::CAPTURED, 'Captured'],
            'partially_captured' => [TransactionStatus::PARTIALLY_CAPTURED, 'Partially Captured'],
            'voided' => [TransactionStatus::VOIDED, 'Voided'],
            'refunded' => [TransactionStatus::REFUNDED, 'Refunded'],
            'partially_refunded' => [TransactionStatus::PARTIALLY_REFUNDED, 'Partially Refunded'],
            'failed' => [TransactionStatus::FAILED, 'Failed'],
            'declined' => [TransactionStatus::DECLINED, 'Declined'],
            'expired' => [TransactionStatus::EXPIRED, 'Expired'],
        ];
    }

    #[Test]
    public function it_identifies_final_statuses(): void
    {
        // Final statuses
        $this->assertTrue(TransactionStatus::CAPTURED->isFinal());
        $this->assertTrue(TransactionStatus::VOIDED->isFinal());
        $this->assertTrue(TransactionStatus::REFUNDED->isFinal());
        $this->assertTrue(TransactionStatus::FAILED->isFinal());
        $this->assertTrue(TransactionStatus::DECLINED->isFinal());
        $this->assertTrue(TransactionStatus::EXPIRED->isFinal());

        // Non-final statuses
        $this->assertFalse(TransactionStatus::PENDING->isFinal());
        $this->assertFalse(TransactionStatus::AUTHORIZED->isFinal());
        $this->assertFalse(TransactionStatus::PARTIALLY_CAPTURED->isFinal());
        $this->assertFalse(TransactionStatus::PARTIALLY_REFUNDED->isFinal());
    }

    #[Test]
    public function it_identifies_capturable_statuses(): void
    {
        // Capturable (using canCapture method)
        $this->assertTrue(TransactionStatus::AUTHORIZED->canCapture());

        // Not capturable
        $this->assertFalse(TransactionStatus::PENDING->canCapture());
        $this->assertFalse(TransactionStatus::CAPTURED->canCapture());
        $this->assertFalse(TransactionStatus::PARTIALLY_CAPTURED->canCapture());
        $this->assertFalse(TransactionStatus::VOIDED->canCapture());
        $this->assertFalse(TransactionStatus::REFUNDED->canCapture());
        $this->assertFalse(TransactionStatus::FAILED->canCapture());
        $this->assertFalse(TransactionStatus::DECLINED->canCapture());
        $this->assertFalse(TransactionStatus::EXPIRED->canCapture());
    }

    #[Test]
    public function it_identifies_refundable_statuses(): void
    {
        // Refundable (using canRefund method)
        $this->assertTrue(TransactionStatus::CAPTURED->canRefund());
        $this->assertTrue(TransactionStatus::PARTIALLY_CAPTURED->canRefund());
        $this->assertTrue(TransactionStatus::PARTIALLY_REFUNDED->canRefund());

        // Not refundable
        $this->assertFalse(TransactionStatus::PENDING->canRefund());
        $this->assertFalse(TransactionStatus::AUTHORIZED->canRefund());
        $this->assertFalse(TransactionStatus::VOIDED->canRefund());
        $this->assertFalse(TransactionStatus::REFUNDED->canRefund());
        $this->assertFalse(TransactionStatus::FAILED->canRefund());
        $this->assertFalse(TransactionStatus::DECLINED->canRefund());
        $this->assertFalse(TransactionStatus::EXPIRED->canRefund());
    }

    #[Test]
    public function it_identifies_voidable_statuses(): void
    {
        // Voidable (using canVoid method)
        $this->assertTrue(TransactionStatus::PENDING->canVoid());
        $this->assertTrue(TransactionStatus::AUTHORIZED->canVoid());

        // Not voidable
        $this->assertFalse(TransactionStatus::CAPTURED->canVoid());
        $this->assertFalse(TransactionStatus::PARTIALLY_CAPTURED->canVoid());
        $this->assertFalse(TransactionStatus::VOIDED->canVoid());
        $this->assertFalse(TransactionStatus::REFUNDED->canVoid());
        $this->assertFalse(TransactionStatus::PARTIALLY_REFUNDED->canVoid());
        $this->assertFalse(TransactionStatus::FAILED->canVoid());
        $this->assertFalse(TransactionStatus::DECLINED->canVoid());
        $this->assertFalse(TransactionStatus::EXPIRED->canVoid());
    }
}
