<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CaptureResult::class)]
final class CaptureResultTest extends TestCase
{
    #[Test]
    public function it_creates_capture_result_with_minimal_parameters(): void
    {
        $result = new CaptureResult(
            success: false,
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->captureId);
        $this->assertNull($result->transactionId);
        $this->assertSame(TransactionStatus::PENDING, $result->status);
        $this->assertNull($result->capturedAmount);
        $this->assertNull($result->feeAmount);
        $this->assertNull($result->netAmount);
        $this->assertNull($result->error);
        $this->assertNull($result->capturedAt);
        $this->assertEmpty($result->rawResponse);
    }

    #[Test]
    public function it_creates_successful_capture(): void
    {
        $amount = Money::of(100.00, 'USD');

        $result = CaptureResult::success(
            captureId: 'cap_123',
            amount: $amount,
            transactionId: 'txn_456',
            rawResponse: ['status' => 'succeeded'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('cap_123', $result->captureId);
        $this->assertSame('txn_456', $result->transactionId);
        $this->assertSame(TransactionStatus::CAPTURED, $result->status);
        $this->assertSame($amount, $result->capturedAmount);
        $this->assertNull($result->feeAmount);
        $this->assertSame($amount, $result->netAmount);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->capturedAt);
        $this->assertSame(['status' => 'succeeded'], $result->rawResponse);
    }

    #[Test]
    public function it_calculates_net_amount_when_fee_provided(): void
    {
        $amount = Money::of(100.00, 'USD');
        $fee = Money::of(2.90, 'USD');

        $result = CaptureResult::success(
            captureId: 'cap_123',
            amount: $amount,
            feeAmount: $fee,
        );

        $expectedNet = Money::of(97.10, 'USD');
        $this->assertEquals($expectedNet->getAmount(), $result->netAmount->getAmount());
    }

    #[Test]
    public function it_creates_failed_capture(): void
    {
        $error = GatewayError::cardDeclined('insufficient_funds');

        $result = CaptureResult::failed(
            error: $error,
            transactionId: 'txn_789',
            rawResponse: ['error' => 'declined'],
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->captureId);
        $this->assertSame('txn_789', $result->transactionId);
        $this->assertSame(TransactionStatus::FAILED, $result->status);
        $this->assertSame($error, $result->error);
        $this->assertSame(['error' => 'declined'], $result->rawResponse);
    }

    #[Test]
    public function it_determines_if_can_refund(): void
    {
        $successfulCapture = CaptureResult::success(
            captureId: 'cap_123',
            amount: Money::of(100.00, 'USD'),
        );
        $this->assertTrue($successfulCapture->canRefund());

        $failedCapture = CaptureResult::failed(
            error: GatewayError::cardDeclined(),
        );
        $this->assertFalse($failedCapture->canRefund());
    }
}
