<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use Nexus\ProcurementOperations\Services\GrIrAccrualService;
use Nexus\ProcurementOperations\Contracts\GrIrAccrualRepositoryInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(GrIrAccrualService::class)]
final class GrIrAccrualServiceTest extends TestCase
{
    private GrIrAccrualService $service;
    private GrIrAccrualRepositoryInterface $repository;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->repository = $this->createMock(GrIrAccrualRepositoryInterface::class);
        $this->service = new GrIrAccrualService(
            eventDispatcher: $this->eventDispatcher,
            repository: $this->repository,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_creates_accrual(): void
    {
        $accrual = $this->service->createAccrual(
            'tenant-1',
            'PO-1',
            'PO-1-NUM',
            'GR-1',
            'GR-1-NUM',
            new \DateTimeImmutable(),
            'V-1',
            'Vendor 1',
            Money::of(100, 'USD'),
            1,
            'user-1'
        );

        $this->assertInstanceOf(GrIrAccrualData::class, $accrual);
        $this->assertEquals('PO-1', $accrual->purchaseOrderId);
        $this->assertEquals('tenant-1', $accrual->metadata['tenant_id']);
    }

    #[Test]
    public function it_creates_accrual_with_zero_lines(): void
    {
        $accrual = $this->service->createAccrual(
            'tenant-1',
            'PO-1',
            'PO-1-NUM',
            'GR-1',
            'GR-1-NUM',
            new \DateTimeImmutable(),
            'V-1',
            'Vendor 1',
            Money::of(100, 'USD'),
            0,
            'user-1'
        );

        $this->assertInstanceOf(GrIrAccrualData::class, $accrual);
        $this->assertEquals(0, $accrual->quantity);
    }

    #[Test]
    public function it_generates_id_without_generator(): void
    {
        $service = new GrIrAccrualService(
            eventDispatcher: $this->eventDispatcher,
            repository: $this->repository,
            logger: new NullLogger(),
            idGenerator: null
        );

        $id = $service->generateAccrualId();
        $this->assertStringStartsWith('accr-', $id);
    }

    #[Test]
    public function it_matches_with_invoice(): void
    {
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'accr-1',
            purchaseOrderId: 'PO-1',
            purchaseOrderLineId: 'L1',
            vendorId: 'V-1',
            productId: 'P-1',
            quantity: 1.0,
            uom: 'EA',
            unitCost: Money::of(100, 'USD'),
            goodsReceiptId: 'GR-1',
            goodsReceiptDate: new \DateTimeImmutable()
        );

        $this->repository->method('getAccrual')->with('accr-1', 'tenant-1')->willReturn($accrual);
        $this->repository->expects($this->once())->method('save');

        $result = $this->service->matchWithInvoice(
            'accr-1',
            'tenant-1',
            'inv-1',
            'inv-num',
            Money::of(100, 'USD'),
            new \DateTimeImmutable(),
            'user-1'
        );

        $this->assertEquals('inv-1', $result->invoiceId);
        $this->assertTrue($result->isMatched());
    }

    #[Test]
    public function it_throws_exception_matching_non_open_accrual(): void
    {
        $accrual = new GrIrAccrualData(
            accrualId: 'accr-1',
            purchaseOrderId: 'PO-1',
            purchaseOrderLineId: 'L1',
            vendorId: 'V-1',
            productId: 'P-1',
            quantity: 1.0,
            uom: 'EA',
            unitCost: Money::of(100, 'USD'),
            totalAccrualAmount: Money::of(100, 'USD'),
            accrualStatus: 'matched',
            goodsReceiptDate: new \DateTimeImmutable()
        );

        $this->repository->method('getAccrual')->with('accr-1', 'tenant-1')->willReturn($accrual);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot match accrual');

        $this->service->matchWithInvoice(
            'accr-1',
            'tenant-1',
            'inv-1',
            'inv-num',
            Money::of(100, 'USD'),
            new \DateTimeImmutable(),
            'user-1'
        );
    }

    #[Test]
    public function it_partial_matches_with_invoice(): void
    {
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'accr-1',
            purchaseOrderId: 'PO-1',
            purchaseOrderLineId: 'L1',
            vendorId: 'V-1',
            productId: 'P-1',
            quantity: 1.0,
            uom: 'EA',
            unitCost: Money::of(100, 'USD'),
            goodsReceiptId: 'GR-1',
            goodsReceiptDate: new \DateTimeImmutable()
        );

        $this->repository->method('getAccrual')->with('accr-1', 'tenant-1')->willReturn($accrual);
        $this->repository->expects($this->once())->method('save');

        $result = $this->service->partialMatchWithInvoice(
            'accr-1',
            'tenant-1',
            'inv-1',
            'inv-num',
            Money::of(50, 'USD'),
            Money::of(50, 'USD'),
            'Partial',
            'user-1'
        );

        $this->assertEquals(50, $result->invoiceAmount->getAmount());
        $this->assertEquals(50, $result->varianceAmount->getAmount());
        $this->assertEquals('Partial', $result->varianceReason);
        $this->assertTrue($result->isPartiallyMatched());
    }

    #[Test]
    public function it_writes_off_accrual(): void
    {
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'accr-1',
            purchaseOrderId: 'PO-1',
            purchaseOrderLineId: 'L1',
            vendorId: 'V-1',
            productId: 'P-1',
            quantity: 1.0,
            uom: 'EA',
            unitCost: Money::of(100, 'USD'),
            goodsReceiptId: 'GR-1',
            goodsReceiptDate: new \DateTimeImmutable()
        );

        $this->repository->method('getAccrual')->with('accr-1', 'tenant-1')->willReturn($accrual);
        $this->repository->expects($this->once())->method('save');

        $result = $this->service->writeOffAccrual('accr-1', 'tenant-1', 'Lost', 'user-1');
        $this->assertTrue($result->isWrittenOff());
    }

    #[Test]
    public function it_reverses_accrual(): void
    {
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: 'accr-1',
            purchaseOrderId: 'PO-1',
            purchaseOrderLineId: 'L1',
            vendorId: 'V-1',
            productId: 'P-1',
            quantity: 1.0,
            uom: 'EA',
            unitCost: Money::of(100, 'USD'),
            goodsReceiptId: 'GR-1',
            goodsReceiptDate: new \DateTimeImmutable()
        );

        $this->repository->method('getAccrual')->with('accr-1', 'tenant-1')->willReturn($accrual);
        $this->repository->expects($this->once())->method('save');

        $result = $this->service->reverseAccrual('accr-1', 'tenant-1', 'Error', 'user-1');
        $this->assertEquals('accr-1', $result->accrualId);
        $this->assertEquals('reversed', $result->accrualStatus);
    }

    #[Test]
    public function it_throws_exception_matching_non_existent_accrual(): void
    {
        $this->repository->method('getAccrual')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Accrual not found');

        $this->service->matchWithInvoice(
            'non-existent',
            'tenant-1',
            'inv-1',
            'inv-1-num',
            Money::of(100, 'USD'),
            new \DateTimeImmutable(),
            'user-1'
        );
    }

    #[Test]
    public function it_returns_real_results_for_queries(): void
    {
        $accrual = GrIrAccrualData::fromGoodsReceipt('accr-1', 'PO-1', 'L1', 'V-1', 'P-1', 1.0, 'EA', Money::of(100, 'USD'), 'GR-1', new \DateTimeImmutable());
        
        $this->repository->method('findUnmatched')->with('tenant-1')->willReturn([$accrual]);
        $this->repository->method('findAged')->with('tenant-1', 30)->willReturn([$accrual]);
        $this->repository->method('findByVendor')->with('tenant-1', 'v-1')->willReturn([$accrual]);
        $this->repository->method('findByPurchaseOrder')->with('tenant-1', 'po-1')->willReturn([$accrual]);
        $this->repository->method('getTotalBalance')->with('tenant-1')->willReturn(Money::of(100, 'USD'));

        $this->assertNotEmpty($this->service->getUnmatchedAccruals('tenant-1'));
        $this->assertNotEmpty($this->service->getAgedAccruals('tenant-1'));
        $this->assertNotEmpty($this->service->getAccrualsByVendor('tenant-1', 'v-1'));
        $this->assertNotEmpty($this->service->getAccrualsByPurchaseOrder('tenant-1', 'po-1'));
        $this->assertEquals(100, $this->service->getTotalAccrualBalance('tenant-1')->getAmount());
    }

    #[Test]
    public function it_generates_aging_report(): void
    {
        $this->repository->method('getTotalBalance')->willReturn(Money::of(500, 'USD'));
        
        $report = $this->service->generateAgingReport('tenant-1', new \DateTimeImmutable());
        $this->assertArrayHasKey('as_of_date', $report);
        $this->assertEquals(500, $report['total_accrual_balance']->getAmount());
    }

    #[Test]
    public function it_suggests_matches_and_auto_matches(): void
    {
        $this->repository->method('suggestMatches')->willReturn([['invoice_id' => 'inv-1', 'score' => 0.9]]);
        
        $this->assertNotEmpty($this->service->suggestMatchingInvoices('accr-1'));
        $result = $this->service->autoMatchAccruals('tenant-1');
        $this->assertEquals(0, $result['matched_count']); // Auto-match logic is still a stub in service
    }

    #[Test]
    public function it_calculates_period_entries(): void
    {
        $result = $this->service->calculatePeriodAccrualEntries('tenant-1', new \DateTimeImmutable());
        $this->assertArrayHasKey('accrual_entries', $result);
    }

    #[Test]
    public function it_hits_get_accrual(): void
    {
        $this->repository->expects($this->once())->method('getAccrual')->with('any', 'tenant-1')->willReturn(null);
        $this->assertNull($this->service->getAccrual('any', 'tenant-1'));
    }
}
