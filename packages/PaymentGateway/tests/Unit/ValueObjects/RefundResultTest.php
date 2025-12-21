<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\RefundType;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefundResult::class)]
final class RefundResultTest extends TestCase
{
    #[Test]
    public function it_creates_refund_result_with_minimal_parameters(): void
    {
        $result = new RefundResult(
            success: false,
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->refundId);
        $this->assertNull($result->transactionId);
        $this->assertSame(TransactionStatus::PENDING, $result->status);
        $this->assertSame(RefundType::FULL, $result->type);
        $this->assertNull($result->refundedAmount);
        $this->assertNull($result->error);
        $this->assertNull($result->reason);
        $this->assertNull($result->refundedAt);
        $this->assertEmpty($result->rawResponse);
    }

    #[Test]
    public function it_creates_successful_full_refund(): void
    {
        $amount = Money::of(100.00, 'USD');

        $result = RefundResult::success(
            refundId: 'ref_123',
            amount: $amount,
            type: RefundType::FULL,
            transactionId: 'txn_456',
            reason: 'Customer requested',
            rawResponse: ['status' => 'succeeded'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('ref_123', $result->refundId);
        $this->assertSame('txn_456', $result->transactionId);
        $this->assertSame(TransactionStatus::REFUNDED, $result->status);
        $this->assertSame(RefundType::FULL, $result->type);
        $this->assertSame($amount, $result->refundedAmount);
        $this->assertSame('Customer requested', $result->reason);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->refundedAt);
        $this->assertTrue($result->isFullRefund());
        $this->assertFalse($result->isPartialRefund());
    }

    #[Test]
    public function it_creates_successful_partial_refund(): void
    {
        $amount = Money::of(25.00, 'USD');

        $result = RefundResult::success(
            refundId: 'ref_789',
            amount: $amount,
            type: RefundType::PARTIAL,
        );

        $this->assertTrue($result->success);
        $this->assertSame(RefundType::PARTIAL, $result->type);
        $this->assertFalse($result->isFullRefund());
        $this->assertTrue($result->isPartialRefund());
    }

    #[Test]
    public function it_creates_failed_refund(): void
    {
        $error = GatewayError::networkError('Timeout occurred');

        $result = RefundResult::failed(
            error: $error,
            transactionId: 'txn_999',
            rawResponse: ['error' => 'timeout'],
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->refundId);
        $this->assertSame('txn_999', $result->transactionId);
        $this->assertSame(TransactionStatus::FAILED, $result->status);
        $this->assertSame($error, $result->error);
        $this->assertSame(['error' => 'timeout'], $result->rawResponse);
    }
}
