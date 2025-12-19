<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Events;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Events\PaymentCancelledEvent;
use Nexus\Payment\Events\PaymentCompletedEvent;
use Nexus\Payment\Events\PaymentCreatedEvent;
use Nexus\Payment\Events\PaymentEvent;
use Nexus\Payment\Events\PaymentFailedEvent;
use Nexus\Payment\Events\PaymentProcessingEvent;
use Nexus\Payment\Events\PaymentReversedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentEvent::class)]
#[CoversClass(PaymentCreatedEvent::class)]
#[CoversClass(PaymentProcessingEvent::class)]
#[CoversClass(PaymentCompletedEvent::class)]
#[CoversClass(PaymentFailedEvent::class)]
#[CoversClass(PaymentCancelledEvent::class)]
#[CoversClass(PaymentReversedEvent::class)]
final class PaymentEventsTest extends TestCase
{
    private DateTimeImmutable $occurredAt;

    protected function setUp(): void
    {
        $this->occurredAt = new DateTimeImmutable('2024-01-15 10:30:00');
    }

    // ========================================================================
    // PaymentCreatedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_created_event(): void
    {
        $amount = Money::of(500.00, 'MYR');

        $event = new PaymentCreatedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            reference: 'REF-2024-001',
            direction: PaymentDirection::INBOUND,
            amount: $amount,
            methodType: PaymentMethodType::CARD,
            payerId: 'customer-001',
            payeeId: 'merchant-001',
            idempotencyKey: 'idem-key-123',
            occurredAt: $this->occurredAt
        );

        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('REF-2024-001', $event->reference);
        $this->assertSame(PaymentDirection::INBOUND, $event->direction);
        $this->assertSame($amount, $event->amount);
        $this->assertSame(PaymentMethodType::CARD, $event->methodType);
        $this->assertSame('customer-001', $event->payerId);
        $this->assertSame('merchant-001', $event->payeeId);
        $this->assertSame('idem-key-123', $event->idempotencyKey);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_created_event_extends_payment_event(): void
    {
        $event = new PaymentCreatedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            reference: 'REF-001',
            direction: PaymentDirection::OUTBOUND,
            amount: Money::of(100.00, 'MYR'),
            methodType: PaymentMethodType::BANK_TRANSFER,
            payerId: 'payer-001',
            payeeId: 'payee-001',
            idempotencyKey: null,
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(PaymentEvent::class, $event);
    }

    #[Test]
    public function it_payment_created_event_with_null_idempotency_key(): void
    {
        $event = new PaymentCreatedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            reference: 'REF-001',
            direction: PaymentDirection::INBOUND,
            amount: Money::of(100.00, 'MYR'),
            methodType: PaymentMethodType::EWALLET,
            payerId: 'payer-001',
            payeeId: 'payee-001',
            idempotencyKey: null,
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->idempotencyKey);
    }

    #[Test]
    public function it_payment_created_event_with_outbound_direction(): void
    {
        $event = new PaymentCreatedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            reference: 'DISB-001',
            direction: PaymentDirection::OUTBOUND,
            amount: Money::of(1000.00, 'MYR'),
            methodType: PaymentMethodType::BANK_TRANSFER,
            payerId: 'company-001',
            payeeId: 'vendor-001',
            idempotencyKey: 'disb-idem-001',
            occurredAt: $this->occurredAt
        );

        $this->assertSame(PaymentDirection::OUTBOUND, $event->direction);
    }

    // ========================================================================
    // PaymentProcessingEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_processing_event(): void
    {
        $amount = Money::of(250.00, 'MYR');

        $event = new PaymentProcessingEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: $amount,
            executorName: 'StripePaymentExecutor',
            providerTransactionId: 'pi_3abc123',
            occurredAt: $this->occurredAt
        );

        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('StripePaymentExecutor', $event->executorName);
        $this->assertSame('pi_3abc123', $event->providerTransactionId);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_processing_event_extends_payment_event(): void
    {
        $event = new PaymentProcessingEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            executorName: 'TestExecutor',
            providerTransactionId: 'txn-001',
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(PaymentEvent::class, $event);
    }

    #[Test]
    public function it_payment_processing_event_with_null_provider_transaction_id(): void
    {
        $event = new PaymentProcessingEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            executorName: 'CashPaymentExecutor',
            providerTransactionId: null,
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->providerTransactionId);
    }

    // ========================================================================
    // PaymentCompletedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_completed_event(): void
    {
        $amount = Money::of(500.00, 'MYR');
        $settledAmount = Money::of(497.50, 'MYR');
        $completedAt = new DateTimeImmutable('2024-01-15 10:35:00');

        $event = new PaymentCompletedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: $amount,
            settledAmount: $settledAmount,
            providerTransactionId: 'pi_completed_123',
            completedAt: $completedAt,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame($settledAmount, $event->settledAmount);
        $this->assertSame('pi_completed_123', $event->providerTransactionId);
        $this->assertSame($completedAt, $event->completedAt);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_completed_event_extends_payment_event(): void
    {
        $event = new PaymentCompletedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            settledAmount: Money::of(100.00, 'MYR'),
            providerTransactionId: 'txn-001',
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(PaymentEvent::class, $event);
    }

    #[Test]
    public function it_detects_settlement_difference(): void
    {
        $event = new PaymentCompletedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            settledAmount: Money::of(97.50, 'MYR'),
            providerTransactionId: 'txn-001',
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->hasSettlementDifference());
    }

    #[Test]
    public function it_no_settlement_difference_when_amounts_equal(): void
    {
        $event = new PaymentCompletedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            settledAmount: Money::of(100.00, 'MYR'),
            providerTransactionId: 'txn-001',
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertFalse($event->hasSettlementDifference());
    }

    #[Test]
    public function it_payment_completed_with_null_provider_transaction_id(): void
    {
        $event = new PaymentCompletedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            settledAmount: Money::of(100.00, 'MYR'),
            providerTransactionId: null,
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->providerTransactionId);
    }

    // ========================================================================
    // PaymentFailedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_failed_event(): void
    {
        $amount = Money::of(500.00, 'MYR');
        $nextRetryAt = new DateTimeImmutable('2024-01-15 11:00:00');

        $event = new PaymentFailedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: $amount,
            failureCode: 'insufficient_funds',
            failureMessage: 'The card has insufficient funds',
            isRetryable: true,
            attemptCount: 1,
            nextRetryAt: $nextRetryAt,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('insufficient_funds', $event->failureCode);
        $this->assertSame('The card has insufficient funds', $event->failureMessage);
        $this->assertTrue($event->isRetryable);
        $this->assertSame(1, $event->attemptCount);
        $this->assertSame($nextRetryAt, $event->nextRetryAt);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_failed_event_extends_payment_event(): void
    {
        $event = new PaymentFailedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'error',
            failureMessage: 'Error message',
            isRetryable: false,
            attemptCount: 3,
            nextRetryAt: null,
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(PaymentEvent::class, $event);
    }

    #[Test]
    public function it_detects_permanent_failure(): void
    {
        $event = new PaymentFailedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'card_declined',
            failureMessage: 'Card was declined',
            isRetryable: false,
            attemptCount: 1,
            nextRetryAt: null,
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->isPermanent());
    }

    #[Test]
    public function it_retryable_failure_is_not_permanent(): void
    {
        $event = new PaymentFailedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'timeout',
            failureMessage: 'Gateway timeout',
            isRetryable: true,
            attemptCount: 1,
            nextRetryAt: new DateTimeImmutable('+1 hour'),
            occurredAt: $this->occurredAt
        );

        $this->assertFalse($event->isPermanent());
    }

    #[Test]
    public function it_handles_multiple_retry_attempts(): void
    {
        $event = new PaymentFailedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'gateway_error',
            failureMessage: 'Gateway temporarily unavailable',
            isRetryable: true,
            attemptCount: 5,
            nextRetryAt: new DateTimeImmutable('+2 hours'),
            occurredAt: $this->occurredAt
        );

        $this->assertSame(5, $event->attemptCount);
        $this->assertTrue($event->isRetryable);
    }

    // ========================================================================
    // PaymentCancelledEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_cancelled_event(): void
    {
        $amount = Money::of(500.00, 'MYR');

        $event = new PaymentCancelledEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: $amount,
            reason: 'Customer requested cancellation',
            cancelledBy: 'user-001',
            occurredAt: $this->occurredAt
        );

        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('Customer requested cancellation', $event->reason);
        $this->assertSame('user-001', $event->cancelledBy);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_cancelled_event_extends_payment_event(): void
    {
        $event = new PaymentCancelledEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            reason: 'Cancelled',
            cancelledBy: 'system',
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(PaymentEvent::class, $event);
    }

    #[Test]
    public function it_payment_cancelled_with_null_cancelled_by(): void
    {
        $event = new PaymentCancelledEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            reason: 'Auto-cancelled due to timeout',
            cancelledBy: null,
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->cancelledBy);
    }

    #[Test]
    public function it_payment_cancelled_with_long_reason(): void
    {
        $longReason = str_repeat('This is a detailed cancellation reason. ', 20);

        $event = new PaymentCancelledEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            reason: $longReason,
            cancelledBy: 'admin-001',
            occurredAt: $this->occurredAt
        );

        $this->assertSame($longReason, $event->reason);
    }

    // ========================================================================
    // PaymentReversedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_reversed_event(): void
    {
        $originalAmount = Money::of(500.00, 'MYR');
        $reversedAmount = Money::of(500.00, 'MYR');

        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: $originalAmount,
            reversedAmount: $reversedAmount,
            reason: 'Customer refund request',
            reversalTransactionId: 'rev-txn-001',
            reversedBy: 'user-001',
            occurredAt: $this->occurredAt
        );

        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($originalAmount, $event->originalAmount);
        $this->assertSame($reversedAmount, $event->reversedAmount);
        $this->assertSame('Customer refund request', $event->reason);
        $this->assertSame('rev-txn-001', $event->reversalTransactionId);
        $this->assertSame('user-001', $event->reversedBy);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_reversed_event_extends_payment_event(): void
    {
        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: Money::of(100.00, 'MYR'),
            reversedAmount: Money::of(100.00, 'MYR'),
            reason: 'Refund',
            reversalTransactionId: null,
            reversedBy: null,
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(PaymentEvent::class, $event);
    }

    #[Test]
    public function it_detects_partial_reversal(): void
    {
        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: Money::of(500.00, 'MYR'),
            reversedAmount: Money::of(200.00, 'MYR'),
            reason: 'Partial refund',
            reversalTransactionId: 'partial-rev-001',
            reversedBy: 'user-001',
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->isPartialReversal());
    }

    #[Test]
    public function it_full_reversal_is_not_partial(): void
    {
        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: Money::of(500.00, 'MYR'),
            reversedAmount: Money::of(500.00, 'MYR'),
            reason: 'Full refund',
            reversalTransactionId: 'full-rev-001',
            reversedBy: 'user-001',
            occurredAt: $this->occurredAt
        );

        $this->assertFalse($event->isPartialReversal());
    }

    #[Test]
    public function it_payment_reversed_with_null_reversal_transaction_id(): void
    {
        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: Money::of(100.00, 'MYR'),
            reversedAmount: Money::of(100.00, 'MYR'),
            reason: 'Cash refund',
            reversalTransactionId: null,
            reversedBy: 'cashier-001',
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->reversalTransactionId);
    }

    #[Test]
    public function it_payment_reversed_with_null_reversed_by(): void
    {
        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: Money::of(100.00, 'MYR'),
            reversedAmount: Money::of(100.00, 'MYR'),
            reason: 'System auto-reversal',
            reversalTransactionId: 'auto-rev-001',
            reversedBy: null,
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->reversedBy);
    }

    // ========================================================================
    // Property Immutability Tests
    // ========================================================================

    #[Test]
    public function it_payment_created_event_properties_are_readonly(): void
    {
        $event = new PaymentCreatedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            reference: 'REF-001',
            direction: PaymentDirection::INBOUND,
            amount: Money::of(100.00, 'MYR'),
            methodType: PaymentMethodType::CARD,
            payerId: 'payer-001',
            payeeId: 'payee-001',
            idempotencyKey: 'key-001',
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('reference')->isReadOnly());
        $this->assertTrue($reflection->getProperty('direction')->isReadOnly());
        $this->assertTrue($reflection->getProperty('amount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('methodType')->isReadOnly());
        $this->assertTrue($reflection->getProperty('payerId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('payeeId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('idempotencyKey')->isReadOnly());
    }

    #[Test]
    public function it_payment_failed_event_properties_are_readonly(): void
    {
        $event = new PaymentFailedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'error',
            failureMessage: 'Error',
            isRetryable: false,
            attemptCount: 1,
            nextRetryAt: null,
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('amount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('failureCode')->isReadOnly());
        $this->assertTrue($reflection->getProperty('failureMessage')->isReadOnly());
        $this->assertTrue($reflection->getProperty('isRetryable')->isReadOnly());
        $this->assertTrue($reflection->getProperty('attemptCount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('nextRetryAt')->isReadOnly());
    }

    #[Test]
    public function it_payment_reversed_event_properties_are_readonly(): void
    {
        $event = new PaymentReversedEvent(
            paymentId: 'payment-001',
            tenantId: 'tenant-001',
            originalAmount: Money::of(100.00, 'MYR'),
            reversedAmount: Money::of(100.00, 'MYR'),
            reason: 'Refund',
            reversalTransactionId: 'rev-001',
            reversedBy: 'user-001',
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('originalAmount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('reversedAmount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('reason')->isReadOnly());
        $this->assertTrue($reflection->getProperty('reversalTransactionId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('reversedBy')->isReadOnly());
    }

    // ========================================================================
    // Base PaymentEvent Tests
    // ========================================================================

    #[Test]
    public function it_base_event_properties_inherited_correctly(): void
    {
        $event = new PaymentCreatedEvent(
            paymentId: 'payment-abc-123',
            tenantId: 'tenant-xyz-789',
            reference: 'REF-001',
            direction: PaymentDirection::INBOUND,
            amount: Money::of(100.00, 'MYR'),
            methodType: PaymentMethodType::CARD,
            payerId: 'payer-001',
            payeeId: 'payee-001',
            idempotencyKey: null,
            occurredAt: $this->occurredAt
        );

        // Test inherited properties from PaymentEvent base class
        $this->assertSame('payment-abc-123', $event->paymentId);
        $this->assertSame('tenant-xyz-789', $event->tenantId);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }
}
