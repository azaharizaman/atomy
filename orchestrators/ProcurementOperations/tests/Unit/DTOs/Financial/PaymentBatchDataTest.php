<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentBatchData::class)]
final class PaymentBatchDataTest extends TestCase
{
    #[Test]
    public function it_creates_payment_batch(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $this->assertSame('batch-001', $batch->batchId);
        $this->assertSame('PMT-2024-001', $batch->batchNumber);
        $this->assertSame('ACH', $batch->paymentMethod);
        $this->assertSame('DRAFT', $batch->status);
        $this->assertSame(0, $batch->itemCount);
        $this->assertSame(0.0, $batch->totalAmount->getAmount());
        $this->assertEmpty($batch->paymentItems);
    }

    #[Test]
    public function it_adds_payment_item_to_batch(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $paymentItem = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001', 'inv-002'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $updatedBatch = $batch->withPaymentItem($paymentItem);

        $this->assertSame(1, $updatedBatch->itemCount);
        $this->assertSame(5000.0, $updatedBatch->totalAmount->getAmount());
        $this->assertCount(1, $updatedBatch->paymentItems);
    }

    #[Test]
    public function it_accumulates_multiple_payment_items(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $item1 = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Vendor One',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $item2 = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-002',
            batchId: 'batch-001',
            vendorId: 'vendor-002',
            vendorName: 'Vendor Two',
            amount: Money::of(3000.00, 'USD'),
            invoiceIds: ['inv-002'],
            vendorBankAccount: '987654321',
            vendorBankRoutingNumber: '021000021',
        );

        $updatedBatch = $batch
            ->withPaymentItem($item1)
            ->withPaymentItem($item2);

        $this->assertSame(2, $updatedBatch->itemCount);
        $this->assertSame(8000.0, $updatedBatch->totalAmount->getAmount());
    }

    #[Test]
    public function it_transitions_to_pending_approval(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $submitted = $batch->withSubmitForApproval();

        $this->assertSame('PENDING_APPROVAL', $submitted->status);
        $this->assertNotNull($submitted->submittedAt);
    }

    #[Test]
    public function it_transitions_to_approved(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        )->withSubmitForApproval();

        $approved = $batch->withApproval('approver-001');

        $this->assertSame('APPROVED', $approved->status);
        $this->assertSame('approver-001', $approved->approvedBy);
        $this->assertNotNull($approved->approvedAt);
    }

    #[Test]
    public function it_transitions_to_processing(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        )
        ->withSubmitForApproval()
        ->withApproval('approver-001');

        $processing = $batch->withProcessing(
            bankFileReference: 'NACHA-20240120-001',
            bankFileName: 'payment_20240120.ach',
        );

        $this->assertSame('PROCESSING', $processing->status);
        $this->assertSame('NACHA-20240120-001', $processing->bankFileReference);
        $this->assertSame('payment_20240120.ach', $processing->bankFileName);
        $this->assertNotNull($processing->processedAt);
    }

    #[Test]
    public function it_transitions_to_completed(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        )
        ->withSubmitForApproval()
        ->withApproval('approver-001')
        ->withProcessing('NACHA-20240120-001', 'payment.ach');

        $completed = $batch->withCompletion();

        $this->assertSame('COMPLETED', $completed->status);
        $this->assertNotNull($completed->completedAt);
    }

    #[Test]
    public function it_gets_vendor_ids(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $item1 = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Vendor One',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $item2 = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-002',
            batchId: 'batch-001',
            vendorId: 'vendor-002',
            vendorName: 'Vendor Two',
            amount: Money::of(3000.00, 'USD'),
            invoiceIds: ['inv-002'],
            vendorBankAccount: '987654321',
            vendorBankRoutingNumber: '021000021',
        );

        $item3 = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-003',
            batchId: 'batch-001',
            vendorId: 'vendor-001', // Same vendor as item1
            vendorName: 'Vendor One',
            amount: Money::of(2000.00, 'USD'),
            invoiceIds: ['inv-003'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $updatedBatch = $batch
            ->withPaymentItem($item1)
            ->withPaymentItem($item2)
            ->withPaymentItem($item3);

        $vendorIds = $updatedBatch->getVendorIds();

        $this->assertCount(2, $vendorIds);
        $this->assertContains('vendor-001', $vendorIds);
        $this->assertContains('vendor-002', $vendorIds);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $array = $batch->toArray();

        $this->assertSame('batch-001', $array['batch_id']);
        $this->assertSame('PMT-2024-001', $array['batch_number']);
        $this->assertSame('ACH', $array['payment_method']);
        $this->assertSame('DRAFT', $array['status']);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertArrayHasKey('payment_items', $array);
    }

    #[Test]
    public function it_checks_status(): void
    {
        $paymentDate = new \DateTimeImmutable('2024-01-20');
        
        $batch = PaymentBatchData::create(
            batchId: 'batch-001',
            batchNumber: 'PMT-2024-001',
            tenantId: 'tenant-001',
            paymentMethod: 'ACH',
            bankAccountId: 'bank-001',
            paymentDate: $paymentDate,
            currency: 'USD',
            createdBy: 'user-001',
        );

        $this->assertTrue($batch->isDraft());
        $this->assertFalse($batch->isPendingApproval());
        $this->assertFalse($batch->isApproved());
        $this->assertFalse($batch->isCompleted());

        $submitted = $batch->withSubmitForApproval();
        $this->assertFalse($submitted->isDraft());
        $this->assertTrue($submitted->isPendingApproval());
    }
}
