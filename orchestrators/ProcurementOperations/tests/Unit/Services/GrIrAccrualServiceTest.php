<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use Nexus\ProcurementOperations\Services\GrIrAccrualService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(GrIrAccrualService::class)]
final class GrIrAccrualServiceTest extends TestCase
{
    private MockableGrIrAccrualService $service;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new MockableGrIrAccrualService(
            eventDispatcher: $this->eventDispatcher,
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
        $service = new MockableGrIrAccrualService(
            eventDispatcher: $this->eventDispatcher,
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

        $this->service->setAccrual($accrual);

        $result = $this->service->matchWithInvoice(
            'accr-1',
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

        $this->service->setAccrual($accrual);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot match accrual');

        $this->service->matchWithInvoice(
            'accr-1',
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

        $this->service->setAccrual($accrual);

        $result = $this->service->partialMatchWithInvoice(
            'accr-1',
            'inv-1',
            'inv-num',
            Money::of(50, 'USD'),
            Money::of(50, 'USD'),
            'Partial',
            'user-1'
        );

        $this->assertEquals(50, $result->invoiceAmount->getAmount());
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

        $this->service->setAccrual($accrual);

        $result = $this->service->writeOffAccrual('accr-1', 'Lost', 'user-1');
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

        $this->service->setAccrual($accrual);

        $result = $this->service->reverseAccrual('accr-1', 'Error', 'user-1');
        $this->assertEquals('accr-1', $result->accrualId);
    }

    #[Test]
    public function it_throws_exception_matching_non_existent_accrual(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Accrual not found');

        $this->service->matchWithInvoice(
            'non-existent',
            'inv-1',
            'inv-1-num',
            Money::of(100, 'USD'),
            new \DateTimeImmutable(),
            'user-1'
        );
    }

    #[Test]
    public function it_returns_empty_results_for_queries(): void
    {
        $this->assertSame([], $this->service->getUnmatchedAccruals('tenant-1'));
        $this->assertSame([], $this->service->getAgedAccruals('tenant-1'));
        $this->assertSame([], $this->service->getAccrualsByVendor('tenant-1', 'v-1'));
        $this->assertSame([], $this->service->getAccrualsByPurchaseOrder('tenant-1', 'po-1'));
        $this->assertEquals(0, $this->service->getTotalAccrualBalance('tenant-1')->getAmount());
    }

    #[Test]
    public function it_generates_aging_report(): void
    {
        $report = $this->service->generateAgingReport('tenant-1', new \DateTimeImmutable());
        $this->assertArrayHasKey('as_of_date', $report);
    }

    #[Test]
    public function it_suggests_matches_and_auto_matches(): void
    {
        $this->assertSame([], $this->service->suggestMatchingInvoices('accr-1'));
        $result = $this->service->autoMatchAccruals('tenant-1');
        $this->assertEquals(0, $result['matched_count']);
    }

    #[Test]
    public function it_calculates_period_entries(): void
    {
        $result = $this->service->calculatePeriodAccrualEntries('tenant-1', new \DateTimeImmutable());
        $this->assertArrayHasKey('accrual_entries', $result);
    }

    #[Test]
    public function it_hits_base_get_accrual(): void
    {
        // This hits line 272 in parent class
        $this->assertNull($this->service->getAccrual('any'));
    }
}

/**
 * Helper class to allow mocking getAccrual
 */
class MockableGrIrAccrualService extends GrIrAccrualService
{
    private ?GrIrAccrualData $mockAccrual = null;

    public function setAccrual(?GrIrAccrualData $accrual): void
    {
        $this->mockAccrual = $accrual;
    }

    public function getAccrual(string $accrualId): ?GrIrAccrualData
    {
        if ($this->mockAccrual && $this->mockAccrual->accrualId === $accrualId) {
            return $this->mockAccrual;
        }
        return parent::getAccrual($accrualId);
    }
}
