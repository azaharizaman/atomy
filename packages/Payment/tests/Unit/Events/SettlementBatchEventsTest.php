<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Events;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Events\PaymentAddedToBatchEvent;
use Nexus\Payment\Events\SettlementBatchClosedEvent;
use Nexus\Payment\Events\SettlementBatchCreatedEvent;
use Nexus\Payment\Events\SettlementBatchDisputedEvent;
use Nexus\Payment\Events\SettlementBatchEvent;
use Nexus\Payment\Events\SettlementBatchReconciledEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettlementBatchEvent::class)]
#[CoversClass(SettlementBatchCreatedEvent::class)]
#[CoversClass(SettlementBatchClosedEvent::class)]
#[CoversClass(SettlementBatchReconciledEvent::class)]
#[CoversClass(SettlementBatchDisputedEvent::class)]
#[CoversClass(PaymentAddedToBatchEvent::class)]
final class SettlementBatchEventsTest extends TestCase
{
    private DateTimeImmutable $occurredAt;

    protected function setUp(): void
    {
        $this->occurredAt = new DateTimeImmutable('2024-01-15 10:30:00');
    }

    // ========================================================================
    // SettlementBatchCreatedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_settlement_batch_created_event(): void
    {
        $event = new SettlementBatchCreatedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'MYR',
            occurredAt: $this->occurredAt
        );

        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('stripe', $event->processorId);
        $this->assertSame('MYR', $event->currency);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_extends_settlement_batch_event(): void
    {
        $event = new SettlementBatchCreatedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'MYR',
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(SettlementBatchEvent::class, $event);
    }

    // ========================================================================
    // SettlementBatchClosedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_settlement_batch_closed_event(): void
    {
        $grossAmount = Money::of(10000.00, 'MYR');
        $totalFees = Money::of(250.00, 'MYR');
        $netAmount = Money::of(9750.00, 'MYR');

        $event = new SettlementBatchClosedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentCount: 50,
            grossAmount: $grossAmount,
            totalFees: $totalFees,
            netAmount: $netAmount,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('stripe', $event->processorId);
        $this->assertSame(50, $event->paymentCount);
        $this->assertSame($grossAmount, $event->grossAmount);
        $this->assertSame($totalFees, $event->totalFees);
        $this->assertSame($netAmount, $event->netAmount);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_closed_event_extends_settlement_batch_event(): void
    {
        $event = new SettlementBatchClosedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentCount: 10,
            grossAmount: Money::of(1000.00, 'MYR'),
            totalFees: Money::of(25.00, 'MYR'),
            netAmount: Money::of(975.00, 'MYR'),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(SettlementBatchEvent::class, $event);
    }

    #[Test]
    public function it_handles_zero_amounts_in_closed_event(): void
    {
        $event = new SettlementBatchClosedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentCount: 0,
            grossAmount: Money::zero('MYR'),
            totalFees: Money::zero('MYR'),
            netAmount: Money::zero('MYR'),
            occurredAt: $this->occurredAt
        );

        $this->assertSame(0, $event->paymentCount);
        $this->assertTrue($event->grossAmount->isZero());
        $this->assertTrue($event->totalFees->isZero());
        $this->assertTrue($event->netAmount->isZero());
    }

    #[Test]
    public function it_closed_event_money_values_are_correct(): void
    {
        $event = new SettlementBatchClosedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentCount: 5,
            grossAmount: Money::of(500.50, 'MYR'),
            totalFees: Money::of(12.51, 'MYR'),
            netAmount: Money::of(487.99, 'MYR'),
            occurredAt: $this->occurredAt
        );

        $this->assertEqualsWithDelta(500.50, $event->grossAmount->getAmount(), 0.001);
        $this->assertEqualsWithDelta(12.51, $event->totalFees->getAmount(), 0.001);
        $this->assertEqualsWithDelta(487.99, $event->netAmount->getAmount(), 0.001);
    }

    // ========================================================================
    // SettlementBatchReconciledEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_settlement_batch_reconciled_event(): void
    {
        $expectedAmount = Money::of(10000.00, 'MYR');
        $actualAmount = Money::of(10000.00, 'MYR');
        $discrepancy = Money::zero('MYR');

        $event = new SettlementBatchReconciledEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            expectedAmount: $expectedAmount,
            actualAmount: $actualAmount,
            discrepancy: $discrepancy,
            hasDiscrepancy: false,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('stripe', $event->processorId);
        $this->assertSame($expectedAmount, $event->expectedAmount);
        $this->assertSame($actualAmount, $event->actualAmount);
        $this->assertSame($discrepancy, $event->discrepancy);
        $this->assertFalse($event->hasDiscrepancy);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_creates_reconciled_event_with_discrepancy(): void
    {
        $expectedAmount = Money::of(10000.00, 'MYR');
        $actualAmount = Money::of(9900.00, 'MYR');
        $discrepancy = Money::of(-100.00, 'MYR');

        $event = new SettlementBatchReconciledEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            expectedAmount: $expectedAmount,
            actualAmount: $actualAmount,
            discrepancy: $discrepancy,
            hasDiscrepancy: true,
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->hasDiscrepancy);
        $this->assertEqualsWithDelta(-100.00, $event->discrepancy->getAmount(), 0.001);
    }

    #[Test]
    public function it_reconciled_event_extends_settlement_batch_event(): void
    {
        $event = new SettlementBatchReconciledEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            expectedAmount: Money::of(1000.00, 'MYR'),
            actualAmount: Money::of(1000.00, 'MYR'),
            discrepancy: Money::zero('MYR'),
            hasDiscrepancy: false,
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(SettlementBatchEvent::class, $event);
    }

    #[Test]
    public function it_reconciled_event_with_positive_discrepancy(): void
    {
        $expectedAmount = Money::of(1000.00, 'MYR');
        $actualAmount = Money::of(1050.00, 'MYR');
        $discrepancy = Money::of(50.00, 'MYR');

        $event = new SettlementBatchReconciledEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            expectedAmount: $expectedAmount,
            actualAmount: $actualAmount,
            discrepancy: $discrepancy,
            hasDiscrepancy: true,
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->hasDiscrepancy);
        $this->assertEqualsWithDelta(50.00, $event->discrepancy->getAmount(), 0.001);
        $this->assertTrue($event->discrepancy->isPositive());
    }

    // ========================================================================
    // SettlementBatchDisputedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_settlement_batch_disputed_event(): void
    {
        $event = new SettlementBatchDisputedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            reason: 'Amount mismatch detected',
            occurredAt: $this->occurredAt
        );

        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('stripe', $event->processorId);
        $this->assertSame('Amount mismatch detected', $event->reason);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disputed_event_extends_settlement_batch_event(): void
    {
        $event = new SettlementBatchDisputedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            reason: 'Missing payments',
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(SettlementBatchEvent::class, $event);
    }

    #[Test]
    public function it_disputed_event_with_long_reason(): void
    {
        $longReason = str_repeat('This is a detailed dispute reason. ', 20);

        $event = new SettlementBatchDisputedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            reason: $longReason,
            occurredAt: $this->occurredAt
        );

        $this->assertSame($longReason, $event->reason);
    }

    // ========================================================================
    // PaymentAddedToBatchEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_payment_added_to_batch_event(): void
    {
        $amount = Money::of(250.00, 'MYR');
        $fee = Money::of(6.25, 'MYR');

        $event = new PaymentAddedToBatchEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentId: 'payment-001',
            amount: $amount,
            fee: $fee,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('stripe', $event->processorId);
        $this->assertSame('payment-001', $event->paymentId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame($fee, $event->fee);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_payment_added_event_extends_settlement_batch_event(): void
    {
        $event = new PaymentAddedToBatchEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentId: 'payment-001',
            amount: Money::of(100.00, 'MYR'),
            fee: Money::of(2.50, 'MYR'),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(SettlementBatchEvent::class, $event);
    }

    #[Test]
    public function it_handles_zero_fee_payment(): void
    {
        $event = new PaymentAddedToBatchEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentId: 'payment-001',
            amount: Money::of(500.00, 'MYR'),
            fee: Money::zero('MYR'),
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->fee->isZero());
        $this->assertEqualsWithDelta(500.00, $event->amount->getAmount(), 0.001);
    }

    #[Test]
    public function it_payment_added_event_with_large_amount(): void
    {
        $event = new PaymentAddedToBatchEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentId: 'payment-001',
            amount: Money::of(999999.99, 'MYR'),
            fee: Money::of(25000.00, 'MYR'),
            occurredAt: $this->occurredAt
        );

        $this->assertEqualsWithDelta(999999.99, $event->amount->getAmount(), 0.001);
        $this->assertEqualsWithDelta(25000.00, $event->fee->getAmount(), 0.001);
    }

    // ========================================================================
    // Property Immutability Tests
    // ========================================================================

    #[Test]
    public function it_created_event_properties_are_readonly(): void
    {
        $event = new SettlementBatchCreatedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'MYR',
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('batchId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('tenantId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('processorId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('currency')->isReadOnly());
        $this->assertTrue($reflection->getProperty('occurredAt')->isReadOnly());
    }

    #[Test]
    public function it_closed_event_properties_are_readonly(): void
    {
        $event = new SettlementBatchClosedEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            paymentCount: 5,
            grossAmount: Money::of(100.00, 'MYR'),
            totalFees: Money::of(2.50, 'MYR'),
            netAmount: Money::of(97.50, 'MYR'),
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('paymentCount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('grossAmount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('totalFees')->isReadOnly());
        $this->assertTrue($reflection->getProperty('netAmount')->isReadOnly());
    }

    #[Test]
    public function it_reconciled_event_properties_are_readonly(): void
    {
        $event = new SettlementBatchReconciledEvent(
            batchId: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            expectedAmount: Money::of(100.00, 'MYR'),
            actualAmount: Money::of(100.00, 'MYR'),
            discrepancy: Money::zero('MYR'),
            hasDiscrepancy: false,
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('expectedAmount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('actualAmount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('discrepancy')->isReadOnly());
        $this->assertTrue($reflection->getProperty('hasDiscrepancy')->isReadOnly());
    }
}
