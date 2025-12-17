<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GrIrAccrualData::class)]
final class GrIrAccrualDataTest extends TestCase
{
    #[Test]
    public function it_creates_accrual_from_goods_receipt(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        $this->assertSame('acc-001', $accrual->accrualId);
        $this->assertSame('tenant-001', $accrual->tenantId);
        $this->assertSame('po-001', $accrual->purchaseOrderId);
        $this->assertSame('PO-2024-001', $accrual->purchaseOrderNumber);
        $this->assertSame('gr-001', $accrual->goodsReceiptId);
        $this->assertSame('GR-2024-001', $accrual->goodsReceiptNumber);
        $this->assertSame(5000.0, $accrual->accrualAmount->getAmount());
        $this->assertSame(3, $accrual->lineCount);
        $this->assertSame('PENDING', $accrual->status);
        $this->assertNull($accrual->invoiceId);
        $this->assertFalse($accrual->isMatched);
        $this->assertFalse($accrual->isWrittenOff);
    }

    #[Test]
    public function it_matches_accrual_with_invoice(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        $invoiceDate = new \DateTimeImmutable('2024-01-20');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        $matched = $accrual->withInvoiceMatch(
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(5000.00, 'USD'),
            invoiceDate: $invoiceDate,
            matchedBy: 'user-002',
        );

        $this->assertTrue($matched->isMatched);
        $this->assertSame('MATCHED', $matched->status);
        $this->assertSame('inv-001', $matched->invoiceId);
        $this->assertSame('INV-2024-001', $matched->invoiceNumber);
        $this->assertSame(5000.0, $matched->invoiceAmount->getAmount());
        $this->assertSame(0.0, $matched->varianceAmount->getAmount());
        $this->assertSame('user-002', $matched->matchedBy);
        $this->assertNotNull($matched->matchedAt);
    }

    #[Test]
    public function it_calculates_variance_on_match(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        $invoiceDate = new \DateTimeImmutable('2024-01-20');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        // Invoice amount is higher than accrual
        $matched = $accrual->withInvoiceMatch(
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(5200.00, 'USD'),
            invoiceDate: $invoiceDate,
            matchedBy: 'user-002',
        );

        $this->assertSame(200.0, $matched->varianceAmount->getAmount());
        $this->assertSame('MATCHED_WITH_VARIANCE', $matched->status);
    }

    #[Test]
    public function it_writes_off_accrual(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        $writtenOff = $accrual->withWriteOff(
            writeOffReason: 'Invoice never received - goods returned',
            writeOffBy: 'manager-001',
            writeOffAccountId: 'gl-acc-999',
        );

        $this->assertTrue($writtenOff->isWrittenOff);
        $this->assertSame('WRITTEN_OFF', $writtenOff->status);
        $this->assertSame('Invoice never received - goods returned', $writtenOff->writeOffReason);
        $this->assertSame('manager-001', $writtenOff->writeOffBy);
        $this->assertSame('gl-acc-999', $writtenOff->writeOffAccountId);
        $this->assertNotNull($writtenOff->writeOffAt);
    }

    #[Test]
    public function it_calculates_aging_days(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        // 15 days after receipt
        $agingDays = $accrual->getAgingDays(new \DateTimeImmutable('2024-01-30'));
        $this->assertSame(15, $agingDays);
    }

    #[Test]
    public function it_checks_if_accrual_is_aged(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        // Not aged at 20 days (threshold 30)
        $this->assertFalse($accrual->isAged(30, new \DateTimeImmutable('2024-02-04')));
        
        // Aged at 35 days
        $this->assertTrue($accrual->isAged(30, new \DateTimeImmutable('2024-02-19')));
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        $array = $accrual->toArray();

        $this->assertSame('acc-001', $array['accrual_id']);
        $this->assertSame('PO-2024-001', $array['purchase_order_number']);
        $this->assertSame('GR-2024-001', $array['goods_receipt_number']);
        $this->assertSame('PENDING', $array['status']);
        $this->assertArrayHasKey('accrual_amount', $array);
        $this->assertArrayHasKey('created_at', $array);
    }

    #[Test]
    public function it_provides_summary(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        $summary = $accrual->getSummary();

        $this->assertStringContainsString('GR-2024-001', $summary);
        $this->assertStringContainsString('PO-2024-001', $summary);
        $this->assertStringContainsString('Test Vendor', $summary);
    }

    #[Test]
    public function it_handles_matched_accrual_not_considered_aged(): void
    {
        $receiptDate = new \DateTimeImmutable('2024-01-15');
        $invoiceDate = new \DateTimeImmutable('2024-01-20');
        
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'acc-001',
            tenantId: 'tenant-001',
            purchaseOrderId: 'po-001',
            purchaseOrderNumber: 'PO-2024-001',
            goodsReceiptId: 'gr-001',
            goodsReceiptNumber: 'GR-2024-001',
            receiptDate: $receiptDate,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            accrualAmount: Money::of(5000.00, 'USD'),
            lineCount: 3,
            createdBy: 'user-001',
        );

        $matched = $accrual->withInvoiceMatch(
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(5000.00, 'USD'),
            invoiceDate: $invoiceDate,
            matchedBy: 'user-002',
        );

        // Even if aging days exceed threshold, matched accrual should not be considered aged
        $this->assertFalse($matched->isAged(30, new \DateTimeImmutable('2024-03-01')));
    }
}
