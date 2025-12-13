<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentItemData::class)]
final class PaymentItemDataTest extends TestCase
{
    #[Test]
    public function it_creates_ach_payment_item(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001', 'inv-002'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $this->assertSame('pmt-item-001', $item->paymentItemId);
        $this->assertSame('batch-001', $item->batchId);
        $this->assertSame('vendor-001', $item->vendorId);
        $this->assertSame('Test Vendor', $item->vendorName);
        $this->assertSame(5000.0, $item->amount->getAmount());
        $this->assertSame('ACH', $item->paymentMethod);
        $this->assertSame('PENDING', $item->status);
        $this->assertSame('123456789', $item->vendorBankAccount);
        $this->assertSame('021000021', $item->vendorBankRoutingNumber);
        $this->assertCount(2, $item->invoiceIds);
    }

    #[Test]
    public function it_creates_wire_payment_item(): void
    {
        $item = PaymentItemData::forWire(
            paymentItemId: 'pmt-item-002',
            batchId: 'batch-001',
            vendorId: 'vendor-002',
            vendorName: 'International Vendor',
            amount: Money::of(25000.00, 'USD'),
            invoiceIds: ['inv-003'],
            vendorBankAccount: 'DE89370400440532013000',
            vendorBankRoutingNumber: '021000021',
            vendorBankSwiftCode: 'COBADEFFXXX',
        );

        $this->assertSame('WIRE', $item->paymentMethod);
        $this->assertSame('COBADEFFXXX', $item->vendorBankSwiftCode);
    }

    #[Test]
    public function it_creates_check_payment_item(): void
    {
        $item = PaymentItemData::forCheck(
            paymentItemId: 'pmt-item-003',
            batchId: 'batch-001',
            vendorId: 'vendor-003',
            vendorName: 'Local Supplier',
            amount: Money::of(1500.00, 'USD'),
            invoiceIds: ['inv-004'],
            checkPayeeName: 'Local Supplier Inc.',
            checkMailingAddress: '123 Main St, Suite 100, Anytown, ST 12345',
        );

        $this->assertSame('CHECK', $item->paymentMethod);
        $this->assertSame('Local Supplier Inc.', $item->checkPayeeName);
        $this->assertSame('123 Main St, Suite 100, Anytown, ST 12345', $item->checkMailingAddress);
    }

    #[Test]
    public function it_transitions_ach_to_processed(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $processed = $item->withAchProcessed('ACH-TRACE-001', 'ACH-BATCH-001');

        $this->assertSame('PROCESSED', $processed->status);
        $this->assertSame('ACH-TRACE-001', $processed->achTraceNumber);
        $this->assertSame('ACH-BATCH-001', $processed->achBatchNumber);
        $this->assertNotNull($processed->processedAt);
    }

    #[Test]
    public function it_transitions_wire_to_processed(): void
    {
        $item = PaymentItemData::forWire(
            paymentItemId: 'pmt-item-002',
            batchId: 'batch-001',
            vendorId: 'vendor-002',
            vendorName: 'International Vendor',
            amount: Money::of(25000.00, 'USD'),
            invoiceIds: ['inv-003'],
            vendorBankAccount: 'DE89370400440532013000',
            vendorBankRoutingNumber: '021000021',
            vendorBankSwiftCode: 'COBADEFFXXX',
        );

        $processed = $item->withWireProcessed('WIRE-REF-001', 'UETR-001');

        $this->assertSame('PROCESSED', $processed->status);
        $this->assertSame('WIRE-REF-001', $processed->wireReferenceNumber);
        $this->assertSame('UETR-001', $processed->wireUetr);
        $this->assertNotNull($processed->processedAt);
    }

    #[Test]
    public function it_transitions_check_to_processed(): void
    {
        $item = PaymentItemData::forCheck(
            paymentItemId: 'pmt-item-003',
            batchId: 'batch-001',
            vendorId: 'vendor-003',
            vendorName: 'Local Supplier',
            amount: Money::of(1500.00, 'USD'),
            invoiceIds: ['inv-004'],
            checkPayeeName: 'Local Supplier Inc.',
            checkMailingAddress: '123 Main St, Suite 100, Anytown, ST 12345',
        );

        $processed = $item->withCheckProcessed('10001');

        $this->assertSame('PROCESSED', $processed->status);
        $this->assertSame('10001', $processed->checkNumber);
        $this->assertNotNull($processed->processedAt);
    }

    #[Test]
    public function it_transitions_to_failed(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $failed = $item->withFailure(
            failureCode: 'INVALID_ACCOUNT',
            failureReason: 'Bank account number is invalid',
            isRetryable: false,
        );

        $this->assertSame('FAILED', $failed->status);
        $this->assertSame('INVALID_ACCOUNT', $failed->failureCode);
        $this->assertSame('Bank account number is invalid', $failed->failureReason);
        $this->assertFalse($failed->isRetryable);
        $this->assertNotNull($failed->failedAt);
    }

    #[Test]
    public function it_transitions_to_completed(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        )->withAchProcessed('ACH-TRACE-001', 'ACH-BATCH-001');

        $completed = $item->withCompletion();

        $this->assertSame('COMPLETED', $completed->status);
        $this->assertNotNull($completed->completedAt);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001', 'inv-002'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $array = $item->toArray();

        $this->assertSame('pmt-item-001', $array['payment_item_id']);
        $this->assertSame('batch-001', $array['batch_id']);
        $this->assertSame('vendor-001', $array['vendor_id']);
        $this->assertSame('ACH', $array['payment_method']);
        $this->assertSame('PENDING', $array['status']);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('invoice_ids', $array);
    }

    #[Test]
    public function it_checks_status(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $this->assertTrue($item->isPending());
        $this->assertFalse($item->isProcessed());
        $this->assertFalse($item->isFailed());
        $this->assertFalse($item->isCompleted());

        $processed = $item->withAchProcessed('ACH-TRACE-001', 'ACH-BATCH-001');
        $this->assertFalse($processed->isPending());
        $this->assertTrue($processed->isProcessed());
    }

    #[Test]
    public function it_gets_display_reference(): void
    {
        $achItem = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        )->withAchProcessed('ACH-TRACE-001', 'ACH-BATCH-001');

        $this->assertSame('ACH-TRACE-001', $achItem->getDisplayReference());

        $wireItem = PaymentItemData::forWire(
            paymentItemId: 'pmt-item-002',
            batchId: 'batch-001',
            vendorId: 'vendor-002',
            vendorName: 'International Vendor',
            amount: Money::of(25000.00, 'USD'),
            invoiceIds: ['inv-003'],
            vendorBankAccount: 'DE89370400440532013000',
            vendorBankRoutingNumber: '021000021',
            vendorBankSwiftCode: 'COBADEFFXXX',
        )->withWireProcessed('WIRE-REF-001', 'UETR-001');

        $this->assertSame('WIRE-REF-001', $wireItem->getDisplayReference());

        $checkItem = PaymentItemData::forCheck(
            paymentItemId: 'pmt-item-003',
            batchId: 'batch-001',
            vendorId: 'vendor-003',
            vendorName: 'Local Supplier',
            amount: Money::of(1500.00, 'USD'),
            invoiceIds: ['inv-004'],
            checkPayeeName: 'Local Supplier Inc.',
            checkMailingAddress: '123 Main St',
        )->withCheckProcessed('10001');

        $this->assertSame('Check #10001', $checkItem->getDisplayReference());
    }

    #[Test]
    public function it_masks_sensitive_bank_account(): void
    {
        $item = PaymentItemData::forAch(
            paymentItemId: 'pmt-item-001',
            batchId: 'batch-001',
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
        );

        $masked = $item->getMaskedBankAccount();

        $this->assertStringContainsString('****', $masked);
        $this->assertStringContainsString('6789', $masked);
        $this->assertStringNotContainsString('12345', $masked);
    }
}
