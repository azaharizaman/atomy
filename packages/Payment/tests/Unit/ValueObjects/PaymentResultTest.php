<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\ValueObjects\PaymentResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentResult::class)]
final class PaymentResultTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_all_fields(): void
    {
        $settledAmount = Money::of(1000, 'USD');
        $timestamp = new \DateTimeImmutable('2024-01-15 10:00:00');

        $result = new PaymentResult(
            success: true,
            paymentId: 'PAY-001',
            providerTransactionId: 'TXN-123',
            settledAmount: $settledAmount,
            failureCode: null,
            failureMessage: null,
            timestamp: $timestamp,
            metadata: ['custom' => 'value'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('PAY-001', $result->paymentId);
        $this->assertSame('TXN-123', $result->providerTransactionId);
        $this->assertTrue($result->settledAmount->equals($settledAmount));
        $this->assertNull($result->failureCode);
        $this->assertNull($result->failureMessage);
        $this->assertSame($timestamp, $result->timestamp);
        $this->assertSame(['custom' => 'value'], $result->metadata);
    }

    #[Test]
    public function it_can_create_success_result(): void
    {
        $settledAmount = Money::of(1000, 'USD');

        $result = PaymentResult::success(
            'PAY-001',
            $settledAmount,
            'TXN-123',
            ['key' => 'value'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('PAY-001', $result->paymentId);
        $this->assertSame('TXN-123', $result->providerTransactionId);
        $this->assertTrue($result->settledAmount->equals($settledAmount));
        $this->assertNull($result->failureCode);
    }

    #[Test]
    public function it_can_create_failure_result(): void
    {
        $result = PaymentResult::failure(
            'PAY-001',
            'INSUFFICIENT_FUNDS',
            'The account has insufficient funds',
            ['retry_allowed' => true],
        );

        $this->assertFalse($result->success);
        $this->assertSame('PAY-001', $result->paymentId);
        $this->assertSame('INSUFFICIENT_FUNDS', $result->failureCode);
        $this->assertSame('The account has insufficient funds', $result->failureMessage);
        $this->assertNull($result->settledAmount);
    }

    #[Test]
    public function it_can_create_pending_result(): void
    {
        $result = PaymentResult::pending(
            'PAY-001',
            'TXN-PENDING-123',
            ['webhook_url' => 'https://example.com/webhook'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('PAY-001', $result->paymentId);
        $this->assertSame('TXN-PENDING-123', $result->providerTransactionId);
        $this->assertSame('pending', $result->metadata['status']);
    }

    #[Test]
    public function it_checks_if_success(): void
    {
        $successResult = PaymentResult::success('PAY-001', Money::of(100, 'USD'));
        $failureResult = PaymentResult::failure('PAY-002', 'ERROR', 'Failed');

        $this->assertTrue($successResult->isSuccess());
        $this->assertFalse($failureResult->isSuccess());
    }

    #[Test]
    public function it_checks_if_failure(): void
    {
        $successResult = PaymentResult::success('PAY-001', Money::of(100, 'USD'));
        $failureResult = PaymentResult::failure('PAY-002', 'ERROR', 'Failed');

        $this->assertFalse($successResult->isFailure());
        $this->assertTrue($failureResult->isFailure());
    }

    #[Test]
    public function it_checks_if_pending(): void
    {
        $pendingResult = PaymentResult::pending('PAY-001');
        $successResult = PaymentResult::success('PAY-002', Money::of(100, 'USD'));
        $failureResult = PaymentResult::failure('PAY-003', 'ERROR', 'Failed');

        $this->assertTrue($pendingResult->isPending());
        $this->assertFalse($successResult->isPending());
        $this->assertFalse($failureResult->isPending());
    }

    #[Test]
    public function it_gets_metadata(): void
    {
        $result = PaymentResult::success(
            'PAY-001',
            Money::of(100, 'USD'),
            null,
            ['custom_field' => 'custom_value'],
        );

        $this->assertSame('custom_value', $result->getMetadata('custom_field'));
        $this->assertNull($result->getMetadata('nonexistent'));
        $this->assertSame('default', $result->getMetadata('nonexistent', 'default'));
    }

    #[Test]
    public function it_checks_if_requires_manual_review(): void
    {
        $normalResult = PaymentResult::success('PAY-001', Money::of(100, 'USD'));
        $reviewResult = PaymentResult::pending(
            'PAY-002',
            null,
            ['requires_review' => true],
        );

        $this->assertFalse($normalResult->requiresManualReview());
        $this->assertTrue($reviewResult->requiresManualReview());
    }

    #[Test]
    public function it_checks_if_retryable(): void
    {
        $retryableFailure = PaymentResult::failure(
            'PAY-001',
            'TIMEOUT',
            'Connection timeout',
            ['retryable' => true],
        );

        $nonRetryableFailure = PaymentResult::failure(
            'PAY-002',
            'INVALID_CARD',
            'Card is invalid',
            ['retryable' => false],
        );

        $this->assertTrue($retryableFailure->isRetryable());
        $this->assertFalse($nonRetryableFailure->isRetryable());
    }

    #[Test]
    public function it_uses_current_time_as_default_timestamp(): void
    {
        $before = new \DateTimeImmutable();
        $result = PaymentResult::success('PAY-001', Money::of(100, 'USD'));
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $result->timestamp);
        $this->assertLessThanOrEqual($after, $result->timestamp);
    }
}
