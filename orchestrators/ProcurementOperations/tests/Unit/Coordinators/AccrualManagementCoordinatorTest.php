<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\AccrualCalculationServiceInterface;
use Nexus\ProcurementOperations\Contracts\FinancialPostingServiceInterface;
use Nexus\ProcurementOperations\Coordinators\AccrualManagementCoordinator;
use Nexus\ProcurementOperations\DTOs\Financial\AccrualAdjustmentData;
use Nexus\ProcurementOperations\DTOs\Financial\AccrualEntryData;
use Nexus\ProcurementOperations\DTOs\Financial\AccrualMatchResult;
use Nexus\ProcurementOperations\Enums\AccrualStatus;
use Nexus\ProcurementOperations\Events\Financial\AccrualAgingAlertEvent;
use Nexus\ProcurementOperations\Events\Financial\AccrualPeriodCloseCompletedEvent;
use Nexus\ProcurementOperations\Events\Financial\AccrualReconciliationCompletedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(AccrualManagementCoordinator::class)]
final class AccrualManagementCoordinatorTest extends TestCase
{
    private AccrualCalculationServiceInterface&MockObject $accrualService;
    private FinancialPostingServiceInterface&MockObject $postingService;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private AccrualManagementCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->accrualService = $this->createMock(AccrualCalculationServiceInterface::class);
        $this->postingService = $this->createMock(FinancialPostingServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->coordinator = new AccrualManagementCoordinator(
            accrualService: $this->accrualService,
            postingService: $this->postingService,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_posts_accrual_successfully(): void
    {
        $receiptData = [
            'goods_receipt_id' => 'GR-001',
            'purchase_order_id' => 'PO-001',
            'vendor_id' => 'VENDOR-001',
            'receipt_date' => new \DateTimeImmutable('2024-01-15'),
            'line_items' => [
                [
                    'item_id' => 'ITEM-001',
                    'quantity' => 100,
                    'unit_price' => Money::of(10, 'USD'),
                ],
            ],
        ];

        $accrualEntry = new AccrualEntryData(
            accrualId: 'ACCR-001',
            goodsReceiptId: 'GR-001',
            purchaseOrderId: 'PO-001',
            vendorId: 'VENDOR-001',
            amount: Money::of(1000, 'USD'),
            currency: 'USD',
            receiptDate: new \DateTimeImmutable('2024-01-15'),
            status: AccrualStatus::OPEN,
            debitAccount: '2100',
            creditAccount: '5000',
        );

        $this->accrualService
            ->method('calculateAccrual')
            ->willReturn($accrualEntry);

        $this->postingService
            ->expects($this->once())
            ->method('postJournalEntry')
            ->willReturn('JE-001');

        $result = $this->coordinator->postAccrual(
            tenantId: 'TENANT-001',
            receiptData: $receiptData,
        );

        $this->assertArrayHasKey('accrual_id', $result);
        $this->assertArrayHasKey('journal_entry_id', $result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('status', $result);

        $this->assertEquals('ACCR-001', $result['accrual_id']);
        $this->assertEquals('JE-001', $result['journal_entry_id']);
        $this->assertEquals('OPEN', $result['status']);
    }

    #[Test]
    public function it_matches_accrual_to_invoice_with_exact_match(): void
    {
        $accrual = new AccrualEntryData(
            accrualId: 'ACCR-001',
            goodsReceiptId: 'GR-001',
            purchaseOrderId: 'PO-001',
            vendorId: 'VENDOR-001',
            amount: Money::of(1000, 'USD'),
            currency: 'USD',
            receiptDate: new \DateTimeImmutable('2024-01-15'),
            status: AccrualStatus::OPEN,
            debitAccount: '2100',
            creditAccount: '5000',
        );

        $invoiceData = [
            'invoice_id' => 'INV-001',
            'vendor_id' => 'VENDOR-001',
            'amount' => Money::of(1000, 'USD'),
            'invoice_date' => new \DateTimeImmutable('2024-01-20'),
        ];

        $matchResult = new AccrualMatchResult(
            accrualId: 'ACCR-001',
            invoiceId: 'INV-001',
            accrualAmount: Money::of(1000, 'USD'),
            invoiceAmount: Money::of(1000, 'USD'),
            variance: Money::of(0, 'USD'),
            variancePercent: 0.0,
            matchStatus: 'EXACT_MATCH',
            matchedAt: new \DateTimeImmutable(),
        );

        $this->accrualService
            ->method('findAccrualById')
            ->willReturn($accrual);

        $this->accrualService
            ->method('matchToInvoice')
            ->willReturn($matchResult);

        $this->postingService
            ->method('postJournalEntry')
            ->willReturn('JE-002');

        $result = $this->coordinator->matchAccrualToInvoice(
            tenantId: 'TENANT-001',
            accrualId: 'ACCR-001',
            invoiceData: $invoiceData,
        );

        $this->assertArrayHasKey('match_status', $result);
        $this->assertArrayHasKey('variance', $result);
        $this->assertArrayHasKey('variance_percent', $result);
        $this->assertArrayHasKey('reversal_entry_id', $result);

        $this->assertEquals('EXACT_MATCH', $result['match_status']);
        $this->assertTrue($result['variance']->isZero());
    }

    #[Test]
    public function it_handles_variance_in_accrual_matching(): void
    {
        $accrual = new AccrualEntryData(
            accrualId: 'ACCR-001',
            goodsReceiptId: 'GR-001',
            purchaseOrderId: 'PO-001',
            vendorId: 'VENDOR-001',
            amount: Money::of(1000, 'USD'),
            currency: 'USD',
            receiptDate: new \DateTimeImmutable('2024-01-15'),
            status: AccrualStatus::OPEN,
            debitAccount: '2100',
            creditAccount: '5000',
        );

        $invoiceData = [
            'invoice_id' => 'INV-001',
            'vendor_id' => 'VENDOR-001',
            'amount' => Money::of(950, 'USD'), // $50 variance
            'invoice_date' => new \DateTimeImmutable('2024-01-20'),
        ];

        $matchResult = new AccrualMatchResult(
            accrualId: 'ACCR-001',
            invoiceId: 'INV-001',
            accrualAmount: Money::of(1000, 'USD'),
            invoiceAmount: Money::of(950, 'USD'),
            variance: Money::of(50, 'USD'),
            variancePercent: 5.0,
            matchStatus: 'WITHIN_TOLERANCE',
            matchedAt: new \DateTimeImmutable(),
        );

        $this->accrualService
            ->method('findAccrualById')
            ->willReturn($accrual);

        $this->accrualService
            ->method('matchToInvoice')
            ->willReturn($matchResult);

        $this->postingService
            ->method('postJournalEntry')
            ->willReturn('JE-002');

        $result = $this->coordinator->matchAccrualToInvoice(
            tenantId: 'TENANT-001',
            accrualId: 'ACCR-001',
            invoiceData: $invoiceData,
        );

        $this->assertEquals('WITHIN_TOLERANCE', $result['match_status']);
        $this->assertEquals(50.0, $result['variance']->getAmount());
        $this->assertEquals(5.0, $result['variance_percent']);
        $this->assertArrayHasKey('variance_entry_id', $result);
    }

    #[Test]
    public function it_processes_auto_matching_for_batch(): void
    {
        $openAccruals = [
            $this->createTestAccrual('ACCR-001', 1000.0, 'VENDOR-001', 'PO-001'),
            $this->createTestAccrual('ACCR-002', 2000.0, 'VENDOR-002', 'PO-002'),
        ];

        $pendingInvoices = [
            [
                'invoice_id' => 'INV-001',
                'vendor_id' => 'VENDOR-001',
                'purchase_order_id' => 'PO-001',
                'amount' => Money::of(1000, 'USD'),
            ],
        ];

        $this->accrualService
            ->method('getOpenAccruals')
            ->willReturn($openAccruals);

        $this->accrualService
            ->method('matchToInvoice')
            ->willReturn(new AccrualMatchResult(
                accrualId: 'ACCR-001',
                invoiceId: 'INV-001',
                accrualAmount: Money::of(1000, 'USD'),
                invoiceAmount: Money::of(1000, 'USD'),
                variance: Money::of(0, 'USD'),
                variancePercent: 0.0,
                matchStatus: 'EXACT_MATCH',
                matchedAt: new \DateTimeImmutable(),
            ));

        $this->postingService
            ->method('postJournalEntry')
            ->willReturn('JE-001');

        $result = $this->coordinator->processAutoMatching(
            tenantId: 'TENANT-001',
            pendingInvoices: $pendingInvoices,
        );

        $this->assertArrayHasKey('matched', $result);
        $this->assertArrayHasKey('unmatched', $result);
        $this->assertArrayHasKey('total_processed', $result);
        $this->assertArrayHasKey('match_rate', $result);

        $this->assertEquals(1, count($result['matched']));
        $this->assertEquals(1, $result['unmatched']); // One accrual has no matching invoice
    }

    #[Test]
    public function it_executes_period_close_and_dispatches_event(): void
    {
        $openAccruals = [
            $this->createTestAccrual('ACCR-001', 1000.0, 'VENDOR-001', 'PO-001', 45), // Aged 45 days
        ];

        $this->accrualService
            ->method('getOpenAccruals')
            ->willReturn($openAccruals);

        $this->accrualService
            ->method('getAgingBreakdown')
            ->willReturn([
                '0-30' => ['count' => 0, 'amount' => Money::of(0, 'USD')],
                '31-60' => ['count' => 1, 'amount' => Money::of(1000, 'USD')],
                '61-90' => ['count' => 0, 'amount' => Money::of(0, 'USD')],
                '90+' => ['count' => 0, 'amount' => Money::of(0, 'USD')],
            ]);

        // Expect both period close and aging alert events
        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                $this->assertThat(
                    $event,
                    $this->logicalOr(
                        $this->isInstanceOf(AccrualPeriodCloseCompletedEvent::class),
                        $this->isInstanceOf(AccrualAgingAlertEvent::class),
                    ),
                );
                return $event;
            });

        $result = $this->coordinator->executePeriodClose(
            tenantId: 'TENANT-001',
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            options: ['aging_alert_days' => 30],
        );

        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('open_accrual_count', $result);
        $this->assertArrayHasKey('total_open_balance', $result);
        $this->assertArrayHasKey('aging_summary', $result);
        $this->assertArrayHasKey('aging_alerts', $result);
        $this->assertArrayHasKey('closed_at', $result);

        $this->assertEquals(1, $result['open_accrual_count']);
        $this->assertCount(1, $result['aging_alerts']);
    }

    #[Test]
    public function it_reconciles_with_gl_and_dispatches_event(): void
    {
        $this->accrualService
            ->method('getTotalOpenAccruals')
            ->willReturn(Money::of(5000, 'USD'));

        $this->postingService
            ->method('getGLBalance')
            ->willReturn(Money::of(5000, 'USD'));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(AccrualReconciliationCompletedEvent::class));

        $result = $this->coordinator->reconcileWithGL(
            tenantId: 'TENANT-001',
            asOfDate: new \DateTimeImmutable('2024-01-31'),
        );

        $this->assertArrayHasKey('subledger_balance', $result);
        $this->assertArrayHasKey('gl_balance', $result);
        $this->assertArrayHasKey('variance', $result);
        $this->assertArrayHasKey('variance_percent', $result);
        $this->assertArrayHasKey('is_reconciled', $result);
        $this->assertArrayHasKey('reconciled_at', $result);

        $this->assertTrue($result['is_reconciled']);
        $this->assertTrue($result['variance']->isZero());
    }

    #[Test]
    public function it_detects_gl_reconciliation_variance(): void
    {
        $this->accrualService
            ->method('getTotalOpenAccruals')
            ->willReturn(Money::of(5000, 'USD'));

        $this->postingService
            ->method('getGLBalance')
            ->willReturn(Money::of(4800, 'USD')); // $200 variance

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $result = $this->coordinator->reconcileWithGL(
            tenantId: 'TENANT-001',
            asOfDate: new \DateTimeImmutable('2024-01-31'),
        );

        $this->assertFalse($result['is_reconciled']);
        $this->assertEquals(200.0, $result['variance']->getAmount());
        $this->assertGreaterThan(0, $result['variance_percent']);
    }

    #[Test]
    public function it_posts_adjustment_with_approval_validation(): void
    {
        $adjustment = new AccrualAdjustmentData(
            adjustmentId: 'ADJ-001',
            accrualId: 'ACCR-001',
            type: 'WRITE_OFF',
            amount: Money::of(100, 'USD'),
            reason: 'Vendor credit received',
            approvedBy: 'MANAGER-001',
            approvedAt: new \DateTimeImmutable(),
        );

        $this->accrualService
            ->method('findAccrualById')
            ->willReturn($this->createTestAccrual('ACCR-001', 1000.0, 'VENDOR-001', 'PO-001'));

        $this->postingService
            ->method('postJournalEntry')
            ->willReturn('JE-ADJ-001');

        $result = $this->coordinator->postAdjustment(
            tenantId: 'TENANT-001',
            adjustment: $adjustment,
        );

        $this->assertArrayHasKey('adjustment_id', $result);
        $this->assertArrayHasKey('journal_entry_id', $result);
        $this->assertArrayHasKey('new_accrual_balance', $result);
        $this->assertArrayHasKey('posted_at', $result);

        $this->assertEquals('ADJ-001', $result['adjustment_id']);
        $this->assertEquals('JE-ADJ-001', $result['journal_entry_id']);
    }

    #[Test]
    public function it_rejects_unapproved_adjustment(): void
    {
        $adjustment = new AccrualAdjustmentData(
            adjustmentId: 'ADJ-001',
            accrualId: 'ACCR-001',
            type: 'WRITE_OFF',
            amount: Money::of(100, 'USD'),
            reason: 'Vendor credit received',
            approvedBy: null, // Not approved
            approvedAt: null,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not approved');

        $this->coordinator->postAdjustment(
            tenantId: 'TENANT-001',
            adjustment: $adjustment,
        );
    }

    #[Test]
    public function it_generates_aging_report_with_vendor_breakdown(): void
    {
        $openAccruals = [
            $this->createTestAccrual('ACCR-001', 1000.0, 'VENDOR-001', 'PO-001', 15),
            $this->createTestAccrual('ACCR-002', 2000.0, 'VENDOR-001', 'PO-002', 45),
            $this->createTestAccrual('ACCR-003', 1500.0, 'VENDOR-002', 'PO-003', 75),
        ];

        $this->accrualService
            ->method('getOpenAccruals')
            ->willReturn($openAccruals);

        $result = $this->coordinator->generateAgingReport(
            tenantId: 'TENANT-001',
            asOfDate: new \DateTimeImmutable('2024-01-31'),
        );

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('aging_buckets', $result);
        $this->assertArrayHasKey('vendor_breakdown', $result);
        $this->assertArrayHasKey('details', $result);

        $this->assertEquals(3, $result['summary']['total_count']);
        $this->assertEquals(4500.0, $result['summary']['total_amount']->getAmount());
        
        // Vendor breakdown
        $this->assertCount(2, $result['vendor_breakdown']);
    }

    #[Test]
    public function it_returns_dashboard_summary(): void
    {
        $openAccruals = [
            $this->createTestAccrual('ACCR-001', 1000.0, 'VENDOR-001', 'PO-001', 15),
        ];

        $this->accrualService
            ->method('getOpenAccruals')
            ->willReturn($openAccruals);

        $this->accrualService
            ->method('getMatchedCount')
            ->willReturn(10);

        $this->accrualService
            ->method('getTotalCount')
            ->willReturn(12);

        $result = $this->coordinator->getDashboardSummary(
            tenantId: 'TENANT-001',
        );

        $this->assertArrayHasKey('open_accruals', $result);
        $this->assertArrayHasKey('total_open_balance', $result);
        $this->assertArrayHasKey('match_rate', $result);
        $this->assertArrayHasKey('aging_breakdown', $result);
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('as_of', $result);

        $this->assertEquals(1, $result['open_accruals']);
        $this->assertGreaterThan(80, $result['match_rate']); // 10/12 = 83.3%
    }

    #[Test]
    #[DataProvider('agingBucketProvider')]
    public function it_assigns_correct_aging_bucket(int $ageDays, string $expectedBucket): void
    {
        // Use reflection to test private method
        $reflector = new \ReflectionClass($this->coordinator);
        $method = $reflector->getMethod('getAgingBucket');
        $method->setAccessible(true);

        $bucket = $method->invoke($this->coordinator, $ageDays);

        $this->assertEquals($expectedBucket, $bucket);
    }

    public static function agingBucketProvider(): array
    {
        return [
            'current' => [15, '0-30'],
            'at_boundary_30' => [30, '0-30'],
            'one_month' => [45, '31-60'],
            'at_boundary_60' => [60, '31-60'],
            'two_months' => [75, '61-90'],
            'at_boundary_90' => [90, '61-90'],
            'over_90' => [120, '90+'],
        ];
    }

    /**
     * Create a test accrual entry.
     */
    private function createTestAccrual(
        string $accrualId,
        float $amount,
        string $vendorId,
        string $purchaseOrderId,
        int $ageDays = 0,
    ): AccrualEntryData {
        $receiptDate = (new \DateTimeImmutable())->modify("-{$ageDays} days");

        return new AccrualEntryData(
            accrualId: $accrualId,
            goodsReceiptId: "GR-{$accrualId}",
            purchaseOrderId: $purchaseOrderId,
            vendorId: $vendorId,
            amount: Money::of($amount, 'USD'),
            currency: 'USD',
            receiptDate: $receiptDate,
            status: AccrualStatus::OPEN,
            debitAccount: '2100',
            creditAccount: '5000',
        );
    }
}
