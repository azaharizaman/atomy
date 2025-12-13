<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Events\Payment;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Events\Payment\EarlyPaymentDiscountCapturedEvent;
use Nexus\ProcurementOperations\Events\Payment\EarlyPaymentDiscountMissedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchApprovedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchProcessedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchRejectedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchSubmittedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentItemFailedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentBatchCreatedEvent::class)]
#[CoversClass(PaymentBatchSubmittedEvent::class)]
#[CoversClass(PaymentBatchApprovedEvent::class)]
#[CoversClass(PaymentBatchRejectedEvent::class)]
#[CoversClass(PaymentBatchProcessedEvent::class)]
#[CoversClass(PaymentItemFailedEvent::class)]
#[CoversClass(EarlyPaymentDiscountCapturedEvent::class)]
#[CoversClass(EarlyPaymentDiscountMissedEvent::class)]
final class PaymentEventsTest extends TestCase
{
    #[Test]
    public function it_creates_payment_batch_created_event(): void
    {
        $event = new PaymentBatchCreatedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            batchReference: 'BATCH-2024-001',
            totalAmount: Money::of(50000.00, 'USD'),
            paymentCount: 10,
            paymentMethod: 'ACH',
            createdBy: 'user-001',
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('BATCH-2024-001', $event->batchReference);
        $this->assertSame(50000.0, $event->totalAmount->getAmount());
        $this->assertSame(10, $event->paymentCount);
        $this->assertSame('ACH', $event->paymentMethod);
        $this->assertSame('user-001', $event->createdBy);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt);
    }

    #[Test]
    public function it_creates_payment_batch_submitted_event(): void
    {
        $event = new PaymentBatchSubmittedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            batchReference: 'BATCH-2024-001',
            totalAmount: Money::of(50000.00, 'USD'),
            paymentCount: 10,
            submittedBy: 'user-001',
            requiredApprovalLevel: 2,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('batch-001', $event->batchId);
        $this->assertSame('user-001', $event->submittedBy);
        $this->assertSame(2, $event->requiredApprovalLevel);
    }

    #[Test]
    public function it_creates_payment_batch_approved_event(): void
    {
        $event = new PaymentBatchApprovedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            batchReference: 'BATCH-2024-001',
            totalAmount: Money::of(50000.00, 'USD'),
            paymentCount: 10,
            approvedBy: 'manager-001',
            approvalLevel: 1,
            approvalNote: 'Approved for payment',
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('manager-001', $event->approvedBy);
        $this->assertSame(1, $event->approvalLevel);
        $this->assertSame('Approved for payment', $event->approvalNote);
    }

    #[Test]
    public function it_creates_payment_batch_rejected_event(): void
    {
        $event = new PaymentBatchRejectedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            batchReference: 'BATCH-2024-001',
            totalAmount: Money::of(50000.00, 'USD'),
            paymentCount: 10,
            rejectedBy: 'manager-001',
            rejectionReason: 'Insufficient documentation for vendor payments',
            rejectionCode: 'INSUFFICIENT_DOCS',
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('manager-001', $event->rejectedBy);
        $this->assertSame('Insufficient documentation for vendor payments', $event->rejectionReason);
        $this->assertSame('INSUFFICIENT_DOCS', $event->rejectionCode);
    }

    #[Test]
    public function it_creates_payment_batch_processed_event(): void
    {
        $event = new PaymentBatchProcessedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            batchReference: 'BATCH-2024-001',
            totalAmount: Money::of(50000.00, 'USD'),
            paymentCount: 10,
            successfulCount: 9,
            failedCount: 1,
            bankFileName: 'payment_20240120.ach',
            bankFileChecksum: 'abc123def456',
            processedAt: new \DateTimeImmutable(),
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame(9, $event->successfulCount);
        $this->assertSame(1, $event->failedCount);
        $this->assertSame('payment_20240120.ach', $event->bankFileName);
        $this->assertSame('abc123def456', $event->bankFileChecksum);
    }

    #[Test]
    public function it_creates_payment_item_failed_event(): void
    {
        $event = new PaymentItemFailedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            paymentItemId: 'pmt-item-005',
            vendorId: 'vendor-003',
            vendorName: 'Failed Vendor',
            amount: Money::of(5000.00, 'USD'),
            failureCode: 'INVALID_ACCOUNT',
            failureReason: 'Bank account number is invalid',
            isRetryable: false,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('pmt-item-005', $event->paymentItemId);
        $this->assertSame('vendor-003', $event->vendorId);
        $this->assertSame('INVALID_ACCOUNT', $event->failureCode);
        $this->assertSame('Bank account number is invalid', $event->failureReason);
        $this->assertFalse($event->isRetryable);
    }

    #[Test]
    public function it_creates_early_payment_discount_captured_event(): void
    {
        $event = new EarlyPaymentDiscountCapturedEvent(
            tenantId: 'tenant-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            vendorId: 'vendor-001',
            vendorName: 'Discount Vendor',
            originalAmount: Money::of(10000.00, 'USD'),
            discountAmount: Money::of(200.00, 'USD'),
            netPaymentAmount: Money::of(9800.00, 'USD'),
            discountTerms: '2/10 Net 30',
            discountPercentage: 2.0,
            daysEarly: 5,
            annualizedReturnRate: 36.73,
            capturedBy: 'user-001',
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('inv-001', $event->invoiceId);
        $this->assertSame(10000.0, $event->originalAmount->getAmount());
        $this->assertSame(200.0, $event->discountAmount->getAmount());
        $this->assertSame(9800.0, $event->netPaymentAmount->getAmount());
        $this->assertSame('2/10 Net 30', $event->discountTerms);
        $this->assertSame(5, $event->daysEarly);
        $this->assertEqualsWithDelta(36.73, $event->annualizedReturnRate, 0.01);
    }

    #[Test]
    public function it_creates_early_payment_discount_missed_event(): void
    {
        $event = new EarlyPaymentDiscountMissedEvent(
            tenantId: 'tenant-001',
            invoiceId: 'inv-002',
            invoiceNumber: 'INV-2024-002',
            vendorId: 'vendor-002',
            vendorName: 'Missed Vendor',
            originalAmount: Money::of(5000.00, 'USD'),
            missedDiscountAmount: Money::of(100.00, 'USD'),
            discountTerms: '2/10 Net 30',
            discountPercentage: 2.0,
            discountDeadline: new \DateTimeImmutable('2024-01-15'),
            missedReason: 'Invoice not approved in time',
            occurredAt: new \DateTimeImmutable(),
        );

        $this->assertSame('inv-002', $event->invoiceId);
        $this->assertSame(100.0, $event->missedDiscountAmount->getAmount());
        $this->assertSame('Invoice not approved in time', $event->missedReason);
    }

    #[Test]
    public function it_converts_payment_batch_created_event_to_array(): void
    {
        $event = new PaymentBatchCreatedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            batchReference: 'BATCH-2024-001',
            totalAmount: Money::of(50000.00, 'USD'),
            paymentCount: 10,
            paymentMethod: 'ACH',
            createdBy: 'user-001',
            occurredAt: new \DateTimeImmutable(),
        );

        $array = $event->toArray();

        $this->assertSame('tenant-001', $array['tenant_id']);
        $this->assertSame('batch-001', $array['batch_id']);
        $this->assertSame('BATCH-2024-001', $array['batch_reference']);
        $this->assertSame(10, $array['payment_count']);
        $this->assertSame('ACH', $array['payment_method']);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertArrayHasKey('occurred_at', $array);
    }

    #[Test]
    public function it_converts_discount_captured_event_to_array(): void
    {
        $event = new EarlyPaymentDiscountCapturedEvent(
            tenantId: 'tenant-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            vendorId: 'vendor-001',
            vendorName: 'Discount Vendor',
            originalAmount: Money::of(10000.00, 'USD'),
            discountAmount: Money::of(200.00, 'USD'),
            netPaymentAmount: Money::of(9800.00, 'USD'),
            discountTerms: '2/10 Net 30',
            discountPercentage: 2.0,
            daysEarly: 5,
            annualizedReturnRate: 36.73,
            capturedBy: 'user-001',
            occurredAt: new \DateTimeImmutable(),
        );

        $array = $event->toArray();

        $this->assertSame('inv-001', $array['invoice_id']);
        $this->assertSame('INV-2024-001', $array['invoice_number']);
        $this->assertSame('2/10 Net 30', $array['discount_terms']);
        $this->assertSame(5, $array['days_early']);
        $this->assertArrayHasKey('discount_amount', $array);
        $this->assertArrayHasKey('annualized_return_rate', $array);
    }

    #[Test]
    public function it_converts_payment_item_failed_event_to_array(): void
    {
        $event = new PaymentItemFailedEvent(
            tenantId: 'tenant-001',
            batchId: 'batch-001',
            paymentItemId: 'pmt-item-005',
            vendorId: 'vendor-003',
            vendorName: 'Failed Vendor',
            amount: Money::of(5000.00, 'USD'),
            failureCode: 'INVALID_ACCOUNT',
            failureReason: 'Bank account number is invalid',
            isRetryable: false,
            occurredAt: new \DateTimeImmutable(),
        );

        $array = $event->toArray();

        $this->assertSame('pmt-item-005', $array['payment_item_id']);
        $this->assertSame('INVALID_ACCOUNT', $array['failure_code']);
        $this->assertFalse($array['is_retryable']);
    }
}
