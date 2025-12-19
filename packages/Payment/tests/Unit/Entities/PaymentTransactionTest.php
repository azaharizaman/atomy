<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Entities;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Entities\PaymentTransaction;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\ValueObjects\ExecutionContext;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentTransaction::class)]
final class PaymentTransactionTest extends TestCase
{
    #[Test]
    public function it_can_be_created_via_constructor(): void
    {
        $reference = PaymentReference::invoice('2024-001');
        $amount = Money::of(1000, 'USD');

        $transaction = new PaymentTransaction(
            id: 'txn-001',
            tenantId: 'tenant-001',
            reference: $reference,
            direction: PaymentDirection::INBOUND,
            amount: $amount,
            methodType: PaymentMethodType::CARD,
            payerId: 'payer-001',
            payeeId: 'payee-001',
            paymentMethodId: 'pm-001',
            idempotencyKey: null,
            executionContext: null,
            createdAt: new \DateTimeImmutable('2024-01-15 10:00:00'),
        );

        $this->assertSame('txn-001', $transaction->getId());
        $this->assertSame('tenant-001', $transaction->getTenantId());
        $this->assertSame($reference, $transaction->getReference());
        $this->assertSame(PaymentDirection::INBOUND, $transaction->getDirection());
        $this->assertSame(PaymentStatus::PENDING, $transaction->getStatus());
        $this->assertTrue($transaction->getAmount()->equals($amount));
        $this->assertSame(PaymentMethodType::CARD, $transaction->getMethodType());
        $this->assertSame('payer-001', $transaction->getPayerId());
        $this->assertSame('payee-001', $transaction->getPayeeId());
    }

    #[Test]
    public function it_can_be_created_via_factory_method(): void
    {
        $reference = PaymentReference::order('ORD-001');
        $amount = Money::of(500, 'USD');
        $idempotencyKey = IdempotencyKey::generate();
        $context = ExecutionContext::now();

        $transaction = PaymentTransaction::create(
            id: 'txn-002',
            tenantId: 'tenant-001',
            reference: $reference,
            direction: PaymentDirection::OUTBOUND,
            amount: $amount,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            payerId: 'payer-001',
            payeeId: 'payee-001',
            paymentMethodId: 'pm-001',
            idempotencyKey: $idempotencyKey,
            executionContext: $context,
        );

        $this->assertSame('txn-002', $transaction->getId());
        $this->assertSame($idempotencyKey, $transaction->getIdempotencyKey());
        $this->assertSame($context, $transaction->getExecutionContext());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getCreatedAt());
    }

    #[Test]
    public function it_starts_in_pending_status(): void
    {
        $transaction = $this->createTransaction();

        $this->assertSame(PaymentStatus::PENDING, $transaction->getStatus());
        $this->assertSame(0, $transaction->getAttemptCount());
    }

    #[Test]
    public function it_can_transition_to_processing(): void
    {
        $transaction = $this->createTransaction();

        $transaction->markAsProcessing('provider-txn-123');

        $this->assertSame(PaymentStatus::PROCESSING, $transaction->getStatus());
        $this->assertSame('provider-txn-123', $transaction->getProviderTransactionId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getProcessedAt());
        $this->assertSame(1, $transaction->getAttemptCount());
    }

    #[Test]
    public function it_increments_attempt_count_on_each_processing(): void
    {
        $transaction = $this->createTransaction();

        $transaction->markAsProcessing();
        $this->assertSame(1, $transaction->getAttemptCount());

        // Simulate retry by resetting status (in reality would be a new attempt)
        $reflection = new \ReflectionProperty($transaction, 'status');
        $reflection->setValue($transaction, PaymentStatus::PENDING);

        $transaction->markAsProcessing();
        $this->assertSame(2, $transaction->getAttemptCount());
    }

    #[Test]
    public function it_can_transition_to_completed(): void
    {
        $transaction = $this->createTransaction();
        $transaction->markAsProcessing();

        $settledAmount = Money::of(1000, 'USD');
        $transaction->markAsCompleted($settledAmount, 'final-txn-id');

        $this->assertSame(PaymentStatus::COMPLETED, $transaction->getStatus());
        $this->assertTrue($transaction->getSettledAmount()->equals($settledAmount));
        $this->assertSame('final-txn-id', $transaction->getProviderTransactionId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getCompletedAt());
        $this->assertTrue($transaction->isSuccessful());
    }

    #[Test]
    public function it_can_transition_to_failed(): void
    {
        $transaction = $this->createTransaction();
        $transaction->markAsProcessing();

        $transaction->markAsFailed('INSUFFICIENT_FUNDS', 'Not enough balance');

        $this->assertSame(PaymentStatus::FAILED, $transaction->getStatus());
        $this->assertSame('INSUFFICIENT_FUNDS', $transaction->getFailureCode());
        $this->assertSame('Not enough balance', $transaction->getFailureMessage());
        $this->assertTrue($transaction->isFailed());
    }

    #[Test]
    public function it_can_transition_to_cancelled(): void
    {
        $transaction = $this->createTransaction();

        $transaction->markAsCancelled('User requested cancellation');

        $this->assertSame(PaymentStatus::CANCELLED, $transaction->getStatus());
        $this->assertSame('User requested cancellation', $transaction->getMetadata()['cancellation_reason']);
    }

    #[Test]
    public function it_can_transition_to_reversed(): void
    {
        $transaction = $this->createTransaction();
        $transaction->markAsProcessing();
        $transaction->markAsCompleted(Money::of(1000, 'USD'));

        $transaction->markAsReversed('Fraudulent transaction', 'reversal-txn-001');

        $this->assertSame(PaymentStatus::REVERSED, $transaction->getStatus());
        $this->assertSame('Fraudulent transaction', $transaction->getMetadata()['reversal_reason']);
        $this->assertSame('reversal-txn-001', $transaction->getMetadata()['reversal_transaction_id']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_transition(): void
    {
        $transaction = $this->createTransaction();
        $transaction->markAsProcessing();
        $transaction->markAsCompleted(Money::of(1000, 'USD'));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot transition from completed to processing');

        $transaction->markAsProcessing();
    }

    #[Test]
    public function it_can_check_if_transition_is_valid(): void
    {
        $transaction = $this->createTransaction();

        $this->assertTrue($transaction->canTransitionTo(PaymentStatus::PROCESSING));
        $this->assertTrue($transaction->canTransitionTo(PaymentStatus::CANCELLED));
        $this->assertFalse($transaction->canTransitionTo(PaymentStatus::COMPLETED));
        $this->assertFalse($transaction->canTransitionTo(PaymentStatus::REVERSED));
    }

    #[Test]
    public function it_reports_terminal_status(): void
    {
        $transaction = $this->createTransaction();
        $this->assertFalse($transaction->isTerminal());

        $transaction->markAsProcessing();
        $this->assertFalse($transaction->isTerminal());

        $transaction->markAsCompleted(Money::of(1000, 'USD'));
        $this->assertTrue($transaction->isTerminal());
    }

    #[Test]
    public function it_reports_if_can_be_cancelled(): void
    {
        $transaction = $this->createTransaction();
        $this->assertTrue($transaction->canBeCancelled());

        $transaction->markAsProcessing();
        $this->assertFalse($transaction->canBeCancelled());
    }

    #[Test]
    public function it_reports_if_can_be_reversed(): void
    {
        $transaction = $this->createTransaction();
        $this->assertFalse($transaction->canBeReversed());

        $transaction->markAsProcessing();
        $transaction->markAsCompleted(Money::of(1000, 'USD'));
        $this->assertTrue($transaction->canBeReversed());
    }

    #[Test]
    public function it_can_add_metadata(): void
    {
        $transaction = $this->createTransaction();

        $transaction->addMetadata(['key1' => 'value1']);
        $transaction->addMetadata(['key2' => 'value2']);

        $metadata = $transaction->getMetadata();
        $this->assertSame('value1', $metadata['key1']);
        $this->assertSame('value2', $metadata['key2']);
    }

    #[Test]
    public function it_returns_original_currency_as_settlement_currency_by_default(): void
    {
        $transaction = $this->createTransaction();

        $this->assertSame('USD', $transaction->getSettlementCurrency());
        $this->assertFalse($transaction->isCrossCurrency());
    }

    #[Test]
    public function it_can_set_settlement_currency_for_cross_currency_transaction(): void
    {
        $transaction = $this->createTransaction();

        $transaction->setSettlementCurrency('EUR');

        $this->assertSame('EUR', $transaction->getSettlementCurrency());
        $this->assertTrue($transaction->isCrossCurrency());
    }

    #[Test]
    public function it_is_not_cross_currency_when_settlement_currency_matches_original(): void
    {
        $transaction = $this->createTransaction();

        $transaction->setSettlementCurrency('USD');

        $this->assertSame('USD', $transaction->getSettlementCurrency());
        $this->assertFalse($transaction->isCrossCurrency());
    }

    private function createTransaction(): PaymentTransaction
    {
        return PaymentTransaction::create(
            id: 'txn-test',
            tenantId: 'tenant-001',
            reference: PaymentReference::invoice('INV-001'),
            direction: PaymentDirection::INBOUND,
            amount: Money::of(1000, 'USD'),
            methodType: PaymentMethodType::CARD,
        );
    }
}
