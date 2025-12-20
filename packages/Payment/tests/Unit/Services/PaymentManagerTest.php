<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\PaymentMethodInterface;
use Nexus\Payment\Contracts\PaymentMethodQueryInterface;
use Nexus\Payment\Contracts\PaymentPersistInterface;
use Nexus\Payment\Contracts\PaymentQueryInterface;
use Nexus\Payment\Contracts\PaymentTransactionInterface;
use Nexus\Payment\Contracts\PaymentExecutorInterface;
use Nexus\Payment\Entities\PaymentTransaction;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\Events\PaymentCancelledEvent;
use Nexus\Payment\Events\PaymentCompletedEvent;
use Nexus\Payment\Events\PaymentCreatedEvent;
use Nexus\Payment\Events\PaymentFailedEvent;
use Nexus\Payment\Events\PaymentProcessingEvent;
use Nexus\Payment\Events\PaymentReversedEvent;
use Nexus\Payment\Exceptions\DuplicatePaymentException;
use Nexus\Payment\Exceptions\InvalidPaymentStatusException;
use Nexus\Payment\Exceptions\PaymentExecutionException;
use Nexus\Payment\Exceptions\PaymentNotFoundException;
use Nexus\Payment\Exceptions\PaymentValidationException;
use Nexus\Payment\Services\PaymentManager;
use Nexus\Payment\Contracts\PaymentValidatorInterface;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;
use Nexus\Payment\ValueObjects\PaymentResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(PaymentManager::class)]
final class PaymentManagerTest extends TestCase
{
    private PaymentQueryInterface&MockObject $paymentQuery;
    private PaymentPersistInterface&MockObject $paymentPersist;
    private PaymentMethodQueryInterface&MockObject $paymentMethodQuery;
    private PaymentValidatorInterface&MockObject $validator;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private PaymentExecutorInterface&MockObject $executor;
    private PaymentManager $manager;

    protected function setUp(): void
    {
        $this->paymentQuery = $this->createMock(PaymentQueryInterface::class);
        $this->paymentPersist = $this->createMock(PaymentPersistInterface::class);
        $this->paymentMethodQuery = $this->createMock(PaymentMethodQueryInterface::class);
        $this->validator = $this->createMock(PaymentValidatorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->executor = $this->createMock(PaymentExecutorInterface::class);

        $this->manager = new PaymentManager(
            paymentQuery: $this->paymentQuery,
            paymentPersist: $this->paymentPersist,
            methodQuery: $this->paymentMethodQuery,
            validator: $this->validator,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            defaultExecutor: $this->executor,
        );
    }

    #[Test]
    public function it_creates_inbound_payment(): void
    {
        $reference = new PaymentReference('INV-001');
        $amount = Money::of(100, 'MYR');

        $this->paymentPersist->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(PaymentTransactionInterface::class));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentCreatedEvent::class));

        $payment = $this->manager->create(
            tenantId: 'tenant_123',
            reference: $reference,
            direction: PaymentDirection::INBOUND,
            amount: $amount,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            payerId: 'customer_123',
        );

        self::assertInstanceOf(PaymentTransactionInterface::class, $payment);
        self::assertSame('tenant_123', $payment->getTenantId());
        self::assertSame(PaymentDirection::INBOUND, $payment->getDirection());
        self::assertTrue($payment->getAmount()->equals($amount));
        self::assertSame(PaymentStatus::PENDING, $payment->getStatus());
    }

    #[Test]
    public function it_creates_outbound_payment(): void
    {
        $reference = new PaymentReference('PO-001');
        $amount = Money::of(500, 'MYR');

        $this->paymentPersist->expects(self::once())
            ->method('save');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch');

        $payment = $this->manager->create(
            tenantId: 'tenant_123',
            reference: $reference,
            direction: PaymentDirection::OUTBOUND,
            amount: $amount,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            payeeId: 'vendor_123',
        );

        self::assertSame(PaymentDirection::OUTBOUND, $payment->getDirection());
    }

    #[Test]
    public function it_creates_payment_with_metadata(): void
    {
        $reference = new PaymentReference('INV-002');
        $amount = Money::of(100, 'MYR');
        $metadata = ['invoice_id' => 'inv_123', 'customer_name' => 'Test Customer'];

        $this->paymentPersist->expects(self::once())->method('save');
        $this->eventDispatcher->expects(self::once())->method('dispatch');

        $payment = $this->manager->create(
            tenantId: 'tenant_123',
            reference: $reference,
            direction: PaymentDirection::INBOUND,
            amount: $amount,
            methodType: PaymentMethodType::CARD,
            payerId: 'customer_123',
            metadata: $metadata,
        );

        self::assertSame($metadata, $payment->getMetadata());
    }

    #[Test]
    public function it_creates_payment_with_idempotency_key(): void
    {
        $reference = new PaymentReference('INV-003');
        $amount = Money::of(100, 'MYR');
        $idempotencyKey = IdempotencyKey::generate();

        $this->paymentQuery->expects(self::once())
            ->method('findByIdempotencyKey')
            ->with($idempotencyKey->getValue())
            ->willReturn(null);

        $this->paymentPersist->expects(self::once())->method('save');
        $this->paymentPersist->expects(self::once())
            ->method('storeIdempotencyKey')
            ->with($idempotencyKey->getValue(), self::anything());

        $payment = $this->manager->create(
            tenantId: 'tenant_123',
            reference: $reference,
            direction: PaymentDirection::INBOUND,
            amount: $amount,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            payerId: 'customer_123',
            idempotencyKey: $idempotencyKey,
        );

        self::assertInstanceOf(PaymentTransactionInterface::class, $payment);
    }

    #[Test]
    public function it_returns_existing_payment_for_duplicate_idempotency_key(): void
    {
        $reference = new PaymentReference('INV-003');
        $amount = Money::of(100, 'MYR');
        $idempotencyKey = IdempotencyKey::generate();

        $existingPayment = $this->createMock(PaymentTransactionInterface::class);
        $existingPayment->method('getTenantId')->willReturn('tenant_123');
        $existingPayment->method('getId')->willReturn('existing_pay_123');

        $this->paymentQuery->expects(self::once())
            ->method('findByIdempotencyKey')
            ->with($idempotencyKey->getValue())
            ->willReturn($existingPayment);

        $this->paymentPersist->expects(self::never())->method('save');

        $this->expectException(DuplicatePaymentException::class);

        $this->manager->create(
            tenantId: 'tenant_123',
            reference: $reference,
            direction: PaymentDirection::INBOUND,
            amount: $amount,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            payerId: 'customer_123',
            idempotencyKey: $idempotencyKey,
        );
    }

    #[Test]
    public function it_executes_pending_payment_successfully(): void
    {
        $amount = Money::of(100, 'MYR');
        $payment = $this->createMockPayment(PaymentStatus::PENDING, $amount);
        $paymentResult = PaymentResult::success('txn_123', Money::of(100, 'MYR'), 'provider_txn_123');

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->with('pay_123')
            ->willReturn($payment);

        $this->executor->expects(self::once())
            ->method('execute')
            ->with($payment)
            ->willReturn($paymentResult);

        $payment->expects(self::once())
            ->method('markAsProcessing');

        $payment->expects(self::once())
            ->method('markAsCompleted')
            ->with(Money::of(100, 'MYR'), 'provider_txn_123');

        $this->paymentPersist->expects(self::exactly(2))->method('save');

        $events = [];
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event;
                return $event;
            });

        $result = $this->manager->execute('pay_123');

        self::assertTrue($result->isSuccess());
        self::assertInstanceOf(PaymentProcessingEvent::class, $events[0]);
        self::assertInstanceOf(PaymentCompletedEvent::class, $events[1]);
    }

    #[Test]
    public function it_handles_payment_execution_failure(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::PENDING);
        $paymentResult = PaymentResult::failure('pay_123', 'DECLINED', 'Insufficient funds');

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->executor->expects(self::once())
            ->method('execute')
            ->willReturn($paymentResult);

        $payment->expects(self::once())
            ->method('markAsFailed')
            ->with('DECLINED', 'Insufficient funds');

        $events = [];
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event;
                return $event;
            });

        $result = $this->manager->execute('pay_123');

        self::assertFalse($result->isSuccess());
        self::assertInstanceOf(PaymentFailedEvent::class, $events[1]);
    }

    #[Test]
    public function it_throws_when_executing_non_pending_payment(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::COMPLETED);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->validator->expects(self::once())
            ->method('validateForExecution')
            ->willThrowException(new InvalidPaymentStatusException(
                PaymentStatus::COMPLETED,
                PaymentStatus::PENDING,
            ));

        $this->expectException(InvalidPaymentStatusException::class);

        $this->manager->execute('pay_123');
    }

    #[Test]
    public function it_throws_when_payment_not_found(): void
    {
        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(PaymentNotFoundException::class);

        $this->manager->execute('pay_nonexistent');
    }

    #[Test]
    public function it_cancels_pending_payment(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::PENDING);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $payment->expects(self::once())
            ->method('markAsCancelled')
            ->with('Customer requested');

        $this->paymentPersist->expects(self::once())->method('save');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentCancelledEvent::class));

        $result = $this->manager->cancel('pay_123', 'Customer requested', 'user_123');

        self::assertInstanceOf(PaymentTransactionInterface::class, $result);
    }

    #[Test]
    public function it_throws_when_cancelling_completed_payment(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::COMPLETED);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->validator->expects(self::once())
            ->method('validateForCancellation')
            ->willThrowException(new InvalidPaymentStatusException(
                PaymentStatus::COMPLETED,
                PaymentStatus::CANCELLED,
            ));

        $this->expectException(InvalidPaymentStatusException::class);

        $this->manager->cancel('pay_123', 'Reason');
    }

    #[Test]
    public function it_reverses_completed_payment(): void
    {
        $amount = Money::of(100, 'MYR');
        $payment = $this->createMockPayment(PaymentStatus::COMPLETED, $amount);
        $refundResult = PaymentResult::success('ref_123', Money::of(100, 'MYR'));

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->executor->expects(self::once())
            ->method('refund')
            ->with('pay_123', 100.0, 'Refund requested')
            ->willReturn($refundResult);

        $payment->expects(self::once())
            ->method('markAsReversed')
            ->with('Refund requested', null);

        $this->paymentPersist->expects(self::once())->method('save');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentReversedEvent::class));

        $result = $this->manager->reverse('pay_123', null, 'Refund requested');

        self::assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_reverses_partial_amount(): void
    {
        $fullAmount = Money::of(100, 'MYR');
        $partialAmount = Money::of(30, 'MYR');
        $payment = $this->createMockPayment(PaymentStatus::COMPLETED, $fullAmount);
        $refundResult = PaymentResult::success('ref_123', Money::of(30, 'MYR'));

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->executor->expects(self::once())
            ->method('refund')
            ->with('pay_123', 30.0, null)
            ->willReturn($refundResult);

        $payment->expects(self::once())
            ->method('markAsReversed')
            ->with(null, null);

        $result = $this->manager->reverse('pay_123', $partialAmount);

        self::assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_throws_when_reversal_amount_exceeds_original(): void
    {
        $originalAmount = Money::of(100, 'MYR');
        $excessAmount = Money::of(150, 'MYR');
        $payment = $this->createMockPayment(PaymentStatus::COMPLETED, $originalAmount);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Reversal amount cannot exceed original payment amount');

        $this->manager->reverse('pay_123', $excessAmount);
    }

    #[Test]
    public function it_throws_when_reversing_pending_payment(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::PENDING);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->validator->expects(self::once())
            ->method('validateForReversal')
            ->willThrowException(new InvalidPaymentStatusException(
                PaymentStatus::PENDING,
                PaymentStatus::COMPLETED,
            ));

        $this->expectException(InvalidPaymentStatusException::class);

        $this->manager->reverse('pay_123');
    }

    #[Test]
    public function it_retries_failed_payment(): void
    {
        $amount = Money::of(100, 'MYR');
        $payment = $this->createMockPayment(PaymentStatus::FAILED, $amount);
        $retryResult = PaymentResult::success('pay_123', Money::of(100, 'MYR'), 'txn_456');

        $this->paymentQuery->method('findById')->willReturn($payment);

        $payment->expects(self::once())
            ->method('markAsProcessing');

        $this->executor->expects(self::once())
            ->method('execute')
            ->willReturn($retryResult);

        $payment->expects(self::once())
            ->method('markAsCompleted')
            ->with(Money::of(100, 'MYR'), 'txn_456');

        $result = $this->manager->retry('pay_123');

        self::assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_throws_when_retrying_non_failed_payment(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('getStatus')->willReturn(PaymentStatus::COMPLETED);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $this->expectException(InvalidPaymentStatusException::class);
        $this->expectExceptionMessage('Only failed payments can be retried');

        $this->manager->retry('pay_123');
    }

    #[Test]
    public function it_gets_payment_status(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::PROCESSING);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->willReturn($payment);

        $status = $this->manager->getStatus('pay_123');

        self::assertSame(PaymentStatus::PROCESSING, $status);
    }

    #[Test]
    public function it_finds_payment_or_fails(): void
    {
        $payment = $this->createMockPayment(PaymentStatus::PENDING);

        $this->paymentQuery->expects(self::once())
            ->method('findById')
            ->with('pay_123')
            ->willReturn($payment);

        $result = $this->manager->findOrFail('pay_123');

        self::assertSame($payment, $result);
    }

    /**
     * Create a mock payment transaction.
     */
    private function createMockPayment(
        PaymentStatus $status,
        ?Money $amount = null,
    ): PaymentTransactionInterface&MockObject {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('getId')->willReturn('pay_123');
        $payment->method('getTenantId')->willReturn('tenant_123');
        $payment->method('getStatus')->willReturn($status);
        $payment->method('getAmount')->willReturn($amount ?? Money::of(100, 'MYR'));
        $payment->method('getMethodType')->willReturn(PaymentMethodType::BANK_ACCOUNT);
        $payment->method('getAttemptCount')->willReturn(1);
        $payment->method('canBeCancelled')->willReturn($status === PaymentStatus::PENDING);
        $payment->method('canBeReversed')->willReturn($status === PaymentStatus::COMPLETED);

        return $payment;
    }
}
