<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\Enums\RefundType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefundRequest::class)]
final class RefundRequestTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_parameters(): void
    {
        $request = new RefundRequest(
            transactionId: 'txn_12345',
        );

        $this->assertSame('txn_12345', $request->transactionId);
        $this->assertSame(RefundType::FULL, $request->type);
        $this->assertNull($request->amount);
        $this->assertNull($request->reason);
        $this->assertSame([], $request->metadata);
        $this->assertNull($request->idempotencyKey);
    }

    #[Test]
    public function it_can_be_created_with_all_parameters(): void
    {
        $amount = Money::of(5000, 'USD');
        $metadata = ['refund_source' => 'customer_request'];

        $request = new RefundRequest(
            transactionId: 'txn_abc',
            type: RefundType::PARTIAL,
            amount: $amount,
            reason: 'Customer returned item',
            metadata: $metadata,
            idempotencyKey: 'idem_ref_123',
        );

        $this->assertSame('txn_abc', $request->transactionId);
        $this->assertSame(RefundType::PARTIAL, $request->type);
        $this->assertSame($amount, $request->amount);
        $this->assertSame('Customer returned item', $request->reason);
        $this->assertSame($metadata, $request->metadata);
        $this->assertSame('idem_ref_123', $request->idempotencyKey);
    }

    #[Test]
    public function it_creates_full_refund_request_via_factory(): void
    {
        $request = RefundRequest::full(
            transactionId: 'txn_full',
            reason: 'Order cancelled',
        );

        $this->assertSame('txn_full', $request->transactionId);
        $this->assertSame(RefundType::FULL, $request->type);
        $this->assertNull($request->amount);
        $this->assertSame('Order cancelled', $request->reason);
    }

    #[Test]
    public function it_creates_full_refund_without_reason(): void
    {
        $request = RefundRequest::full(
            transactionId: 'txn_no_reason',
        );

        $this->assertSame('txn_no_reason', $request->transactionId);
        $this->assertSame(RefundType::FULL, $request->type);
        $this->assertNull($request->reason);
    }

    #[Test]
    public function it_creates_partial_refund_request_via_factory(): void
    {
        $amount = Money::of(2500, 'EUR');

        $request = RefundRequest::partial(
            transactionId: 'txn_partial',
            amount: $amount,
            reason: 'Partial item return',
        );

        $this->assertSame('txn_partial', $request->transactionId);
        $this->assertSame(RefundType::PARTIAL, $request->type);
        $this->assertSame($amount, $request->amount);
        $this->assertSame('Partial item return', $request->reason);
    }

    #[Test]
    public function it_detects_full_refund(): void
    {
        $request = RefundRequest::full('txn_test');

        $this->assertTrue($request->isFullRefund());
    }

    #[Test]
    public function it_detects_non_full_refund(): void
    {
        $request = RefundRequest::partial('txn_test', Money::of(1000, 'USD'));

        $this->assertFalse($request->isFullRefund());
    }

    #[Test]
    public function it_returns_amount_in_minor_units_when_set(): void
    {
        $request = RefundRequest::partial(
            transactionId: 'txn_test',
            amount: new Money(8750, 'GBP'),
        );

        $this->assertSame(8750, $request->getAmountInMinorUnits());
    }

    #[Test]
    public function it_returns_null_for_amount_in_minor_units_when_not_set(): void
    {
        $request = RefundRequest::full('txn_test');

        $this->assertNull($request->getAmountInMinorUnits());
    }
}
