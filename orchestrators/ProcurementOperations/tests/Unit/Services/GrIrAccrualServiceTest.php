<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use Nexus\ProcurementOperations\Enums\AccrualStatus;
use Nexus\ProcurementOperations\Services\GrIrAccrualService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(GrIrAccrualService::class)]
final class GrIrAccrualServiceTest extends TestCase
{
    private GrIrAccrualService $service;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new GrIrAccrualService(
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_creates_accrual_from_goods_receipt(): void
    {
        $accrualAmount = Money::of(5000.00, 'USD');

        $accrual = $this->service->createAccrual(
            purchaseOrderId: 'PO-001',
            purchaseOrderLineId: 'PO-001-L1',
            goodsReceiptId: 'GR-001',
            vendorId: 'VENDOR-001',
            accrualAmount: $accrualAmount,
            receiptDate: new \DateTimeImmutable(),
            description: 'Goods received - pending invoice',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        $this->assertInstanceOf(GrIrAccrualData::class, $accrual);
        $this->assertNotEmpty($accrual->accrualId);
        $this->assertEquals('PO-001', $accrual->purchaseOrderId);
        $this->assertEquals('GR-001', $accrual->goodsReceiptId);
        $this->assertEquals('VENDOR-001', $accrual->vendorId);
        $this->assertEquals(5000.00, $accrual->accrualAmount->getAmount());
        $this->assertEquals(AccrualStatus::OPEN, $accrual->status);
        $this->assertEquals(0.00, $accrual->matchedAmount->getAmount());
    }

    #[Test]
    public function it_fully_matches_accrual_with_invoice(): void
    {
        // Create accrual
        $accrual = $this->service->createAccrual(
            purchaseOrderId: 'PO-001',
            purchaseOrderLineId: 'PO-001-L1',
            goodsReceiptId: 'GR-001',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(5000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-5 days'),
            description: 'Goods received',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        // Match with invoice
        $matchedAccrual = $this->service->matchWithInvoice(
            accrualId: $accrual->accrualId,
            invoiceId: 'INV-001',
            invoiceAmount: Money::of(5000.00, 'USD'),
            matchedBy: 'USER-001',
        );

        $this->assertEquals(AccrualStatus::MATCHED, $matchedAccrual->status);
        $this->assertEquals('INV-001', $matchedAccrual->invoiceId);
        $this->assertEquals(5000.00, $matchedAccrual->matchedAmount->getAmount());
        $this->assertInstanceOf(\DateTimeImmutable::class, $matchedAccrual->matchedDate);
    }

    #[Test]
    public function it_partially_matches_accrual_with_invoice(): void
    {
        // Create accrual
        $accrual = $this->service->createAccrual(
            purchaseOrderId: 'PO-001',
            purchaseOrderLineId: 'PO-001-L1',
            goodsReceiptId: 'GR-001',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(5000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-5 days'),
            description: 'Goods received',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        // Partial match with invoice
        $matchedAccrual = $this->service->partialMatchWithInvoice(
            accrualId: $accrual->accrualId,
            invoiceId: 'INV-001',
            matchedAmount: Money::of(3000.00, 'USD'),
            matchedBy: 'USER-001',
        );

        $this->assertEquals(AccrualStatus::PARTIAL, $matchedAccrual->status);
        $this->assertEquals('INV-001', $matchedAccrual->invoiceId);
        $this->assertEquals(3000.00, $matchedAccrual->matchedAmount->getAmount());
        // Remaining: 5000 - 3000 = 2000
    }

    #[Test]
    public function it_writes_off_unmatched_accrual(): void
    {
        // Create accrual
        $accrual = $this->service->createAccrual(
            purchaseOrderId: 'PO-001',
            purchaseOrderLineId: 'PO-001-L1',
            goodsReceiptId: 'GR-001',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(500.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-120 days'),
            description: 'Old goods receipt - no invoice',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        // Write off the accrual
        $writtenOff = $this->service->writeOffAccrual(
            accrualId: $accrual->accrualId,
            writeOffAmount: Money::of(500.00, 'USD'),
            writeOffReason: 'Invoice will not be received - vendor dispute resolved',
            approvedBy: 'APPROVER-001',
        );

        $this->assertEquals(AccrualStatus::WRITTEN_OFF, $writtenOff->status);
    }

    #[Test]
    public function it_returns_unmatched_accruals(): void
    {
        // Create multiple accruals
        $this->service->createAccrual(
            purchaseOrderId: 'PO-001',
            purchaseOrderLineId: 'PO-001-L1',
            goodsReceiptId: 'GR-001',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(5000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-30 days'),
            description: 'Accrual 1',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        $this->service->createAccrual(
            purchaseOrderId: 'PO-002',
            purchaseOrderLineId: 'PO-002-L1',
            goodsReceiptId: 'GR-002',
            vendorId: 'VENDOR-002',
            accrualAmount: Money::of(3000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-15 days'),
            description: 'Accrual 2',
            costCenterId: 'CC-002',
            glAccountId: 'GL-2100',
        );

        $unmatched = $this->service->getUnmatchedAccruals();

        $this->assertIsArray($unmatched);
        $this->assertCount(2, $unmatched);

        foreach ($unmatched as $accrual) {
            $this->assertInstanceOf(GrIrAccrualData::class, $accrual);
            $this->assertEquals(AccrualStatus::OPEN, $accrual->status);
        }
    }

    #[Test]
    public function it_returns_aged_accruals(): void
    {
        // Create an old accrual
        $this->service->createAccrual(
            purchaseOrderId: 'PO-OLD',
            purchaseOrderLineId: 'PO-OLD-L1',
            goodsReceiptId: 'GR-OLD',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(10000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-100 days'),
            description: 'Old unmatched accrual',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        // Create a recent accrual
        $this->service->createAccrual(
            purchaseOrderId: 'PO-NEW',
            purchaseOrderLineId: 'PO-NEW-L1',
            goodsReceiptId: 'GR-NEW',
            vendorId: 'VENDOR-002',
            accrualAmount: Money::of(2000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-10 days'),
            description: 'Recent accrual',
            costCenterId: 'CC-002',
            glAccountId: 'GL-2100',
        );

        // Get accruals older than 90 days
        $aged = $this->service->getAgedAccruals(thresholdDays: 90);

        $this->assertIsArray($aged);
        $this->assertCount(1, $aged);
        $this->assertEquals('PO-OLD', $aged[0]->purchaseOrderId);
    }

    #[Test]
    public function it_generates_aging_report(): void
    {
        // Create accruals at different ages
        $this->service->createAccrual(
            purchaseOrderId: 'PO-1',
            purchaseOrderLineId: 'PO-1-L1',
            goodsReceiptId: 'GR-1',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(1000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-15 days'),
            description: '0-30 days',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        $this->service->createAccrual(
            purchaseOrderId: 'PO-2',
            purchaseOrderLineId: 'PO-2-L1',
            goodsReceiptId: 'GR-2',
            vendorId: 'VENDOR-002',
            accrualAmount: Money::of(2000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-45 days'),
            description: '31-60 days',
            costCenterId: 'CC-002',
            glAccountId: 'GL-2100',
        );

        $report = $this->service->generateAgingReport();

        $this->assertArrayHasKey('buckets', $report);
        $this->assertArrayHasKey('total_open_accruals', $report);
        $this->assertArrayHasKey('total_amount', $report);
        $this->assertArrayHasKey('generated_at', $report);

        // Should have aging buckets
        $buckets = $report['buckets'];
        $this->assertArrayHasKey('0-30', $buckets);
        $this->assertArrayHasKey('31-60', $buckets);
        $this->assertArrayHasKey('61-90', $buckets);
        $this->assertArrayHasKey('90+', $buckets);
    }

    #[Test]
    public function it_suggests_matching_invoices_for_accrual(): void
    {
        $accrual = $this->service->createAccrual(
            purchaseOrderId: 'PO-001',
            purchaseOrderLineId: 'PO-001-L1',
            goodsReceiptId: 'GR-001',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(5000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-10 days'),
            description: 'Goods received',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        // Mock available invoices
        $availableInvoices = [
            [
                'invoice_id' => 'INV-001',
                'vendor_id' => 'VENDOR-001',
                'po_reference' => 'PO-001',
                'amount' => Money::of(5000.00, 'USD'),
                'invoice_date' => new \DateTimeImmutable('-5 days'),
            ],
            [
                'invoice_id' => 'INV-002',
                'vendor_id' => 'VENDOR-001',
                'po_reference' => 'PO-002',
                'amount' => Money::of(4800.00, 'USD'),
                'invoice_date' => new \DateTimeImmutable('-3 days'),
            ],
        ];

        $suggestions = $this->service->suggestMatchingInvoices(
            accrual: $accrual,
            availableInvoices: $availableInvoices,
        );

        $this->assertIsArray($suggestions);
        // INV-001 should be suggested as it matches PO and amount exactly
        $this->assertNotEmpty($suggestions);

        // First suggestion should be the best match
        $bestMatch = $suggestions[0];
        $this->assertEquals('INV-001', $bestMatch['invoice_id']);
        $this->assertGreaterThan(0.9, $bestMatch['confidence_score']);
    }

    #[Test]
    public function it_auto_matches_accruals_with_exact_matches(): void
    {
        // Create an accrual
        $this->service->createAccrual(
            purchaseOrderId: 'PO-AUTO',
            purchaseOrderLineId: 'PO-AUTO-L1',
            goodsReceiptId: 'GR-AUTO',
            vendorId: 'VENDOR-AUTO',
            accrualAmount: Money::of(7500.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-20 days'),
            description: 'For auto-matching',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        // Mock invoices for auto-matching
        $pendingInvoices = [
            [
                'invoice_id' => 'INV-AUTO',
                'vendor_id' => 'VENDOR-AUTO',
                'po_reference' => 'PO-AUTO',
                'amount' => Money::of(7500.00, 'USD'),
            ],
        ];

        $result = $this->service->autoMatchAccruals(
            pendingInvoices: $pendingInvoices,
            tolerancePercentage: 0.0,
            matchedBy: 'SYSTEM',
        );

        $this->assertArrayHasKey('matched_count', $result);
        $this->assertArrayHasKey('matched_pairs', $result);
        $this->assertArrayHasKey('unmatched_accruals', $result);
        $this->assertArrayHasKey('unmatched_invoices', $result);

        $this->assertEquals(1, $result['matched_count']);
    }

    #[Test]
    public function it_reverses_matched_accrual(): void
    {
        // Create and match an accrual
        $accrual = $this->service->createAccrual(
            purchaseOrderId: 'PO-REV',
            purchaseOrderLineId: 'PO-REV-L1',
            goodsReceiptId: 'GR-REV',
            vendorId: 'VENDOR-REV',
            accrualAmount: Money::of(3000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-30 days'),
            description: 'To be reversed',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        $matched = $this->service->matchWithInvoice(
            accrualId: $accrual->accrualId,
            invoiceId: 'INV-REV',
            invoiceAmount: Money::of(3000.00, 'USD'),
            matchedBy: 'USER-001',
        );

        // Reverse the match
        $reversed = $this->service->reverseAccrual(
            accrualId: $matched->accrualId,
            reversalReason: 'Invoice was cancelled',
            reversedBy: 'USER-001',
        );

        $this->assertEquals(AccrualStatus::REVERSED, $reversed->status);
    }

    #[Test]
    public function it_calculates_period_accrual_entries(): void
    {
        // Create some accruals for the period
        $this->service->createAccrual(
            purchaseOrderId: 'PO-P1',
            purchaseOrderLineId: 'PO-P1-L1',
            goodsReceiptId: 'GR-P1',
            vendorId: 'VENDOR-001',
            accrualAmount: Money::of(10000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-5 days'),
            description: 'Period accrual 1',
            costCenterId: 'CC-001',
            glAccountId: 'GL-5000',
        );

        $this->service->createAccrual(
            purchaseOrderId: 'PO-P2',
            purchaseOrderLineId: 'PO-P2-L1',
            goodsReceiptId: 'GR-P2',
            vendorId: 'VENDOR-002',
            accrualAmount: Money::of(5000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-3 days'),
            description: 'Period accrual 2',
            costCenterId: 'CC-002',
            glAccountId: 'GL-5100',
        );

        $periodStart = new \DateTimeImmutable('-30 days');
        $periodEnd = new \DateTimeImmutable();

        $entries = $this->service->calculatePeriodAccrualEntries(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        $this->assertArrayHasKey('journal_entries', $entries);
        $this->assertArrayHasKey('total_accrual_amount', $entries);
        $this->assertArrayHasKey('accrual_count', $entries);
        $this->assertArrayHasKey('period', $entries);

        // Total should be 15000
        $this->assertEquals(15000.00, $entries['total_accrual_amount']->getAmount());
        $this->assertEquals(2, $entries['accrual_count']);
    }

    #[Test]
    public function it_handles_vendor_filter_for_unmatched_accruals(): void
    {
        // Create accruals for different vendors
        $this->service->createAccrual(
            purchaseOrderId: 'PO-V1',
            purchaseOrderLineId: 'PO-V1-L1',
            goodsReceiptId: 'GR-V1',
            vendorId: 'VENDOR-ALPHA',
            accrualAmount: Money::of(1000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-10 days'),
            description: 'Vendor Alpha accrual',
            costCenterId: 'CC-001',
            glAccountId: 'GL-2100',
        );

        $this->service->createAccrual(
            purchaseOrderId: 'PO-V2',
            purchaseOrderLineId: 'PO-V2-L1',
            goodsReceiptId: 'GR-V2',
            vendorId: 'VENDOR-BETA',
            accrualAmount: Money::of(2000.00, 'USD'),
            receiptDate: new \DateTimeImmutable('-10 days'),
            description: 'Vendor Beta accrual',
            costCenterId: 'CC-002',
            glAccountId: 'GL-2100',
        );

        // Get unmatched accruals for specific vendor
        $vendorAlphaAccruals = $this->service->getUnmatchedAccruals(vendorId: 'VENDOR-ALPHA');

        $this->assertCount(1, $vendorAlphaAccruals);
        $this->assertEquals('VENDOR-ALPHA', $vendorAlphaAccruals[0]->vendorId);
    }
}
