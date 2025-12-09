<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Memo;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payable\Contracts\VendorInvoiceInterface;
use Nexus\Payable\Contracts\VendorInvoicePersistInterface;
use Nexus\Payable\Contracts\VendorInvoiceQueryInterface;
use Nexus\ProcurementOperations\Contracts\MemoInterface;
use Nexus\ProcurementOperations\Contracts\MemoPersistInterface;
use Nexus\ProcurementOperations\Contracts\MemoQueryInterface;
use Nexus\ProcurementOperations\DTOs\MemoApplicationRequest;
use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Enums\MemoType;
use Nexus\ProcurementOperations\Events\MemoAppliedEvent;
use Nexus\ProcurementOperations\Exceptions\MemoApplicationException;
use Nexus\ProcurementOperations\Exceptions\MemoNotFoundException;
use Nexus\ProcurementOperations\Services\Memo\MemoApplicationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class MemoApplicationServiceTest extends TestCase
{
    private MemoQueryInterface&MockObject $memoQuery;
    private MemoPersistInterface&MockObject $memoPersist;
    private VendorInvoiceQueryInterface&MockObject $invoiceQuery;
    private VendorInvoicePersistInterface&MockObject $invoicePersist;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MemoApplicationService $service;

    protected function setUp(): void
    {
        $this->memoQuery = $this->createMock(MemoQueryInterface::class);
        $this->memoPersist = $this->createMock(MemoPersistInterface::class);
        $this->invoiceQuery = $this->createMock(VendorInvoiceQueryInterface::class);
        $this->invoicePersist = $this->createMock(VendorInvoicePersistInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new MemoApplicationService(
            memoQuery: $this->memoQuery,
            memoPersist: $this->memoPersist,
            invoiceQuery: $this->invoiceQuery,
            invoicePersist: $this->invoicePersist,
            eventDispatcher: $this->eventDispatcher,
        );
    }

    public function test_apply_memo_reduces_invoice_balance(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(10000, 'MYR'),
        );

        $invoice = $this->createInvoiceMock(
            id: 'invoice-1',
            vendorId: 'vendor-1',
            balance: Money::of(25000, 'MYR'),
        );

        $this->memoQuery->method('findById')->willReturn($memo);
        $this->invoiceQuery->method('findById')->willReturn($invoice);

        $updatedMemo = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::APPLIED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(0, 'MYR'),
        );

        $this->memoPersist->method('recordApplication')->willReturn($updatedMemo);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(MemoAppliedEvent::class));

        $request = new MemoApplicationRequest(
            memoId: 'memo-1',
            invoiceId: 'invoice-1',
            amount: Money::of(10000, 'MYR'),
        );

        $result = $this->service->apply($request);

        $this->assertTrue($result->success);
        $this->assertSame(10000, $result->appliedAmount->getAmountInCents());
    }

    public function test_apply_memo_throws_exception_when_memo_not_found(): void
    {
        $this->memoQuery->method('findById')->willReturn(null);

        $this->expectException(MemoNotFoundException::class);

        $request = new MemoApplicationRequest(
            memoId: 'non-existent',
            invoiceId: 'invoice-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->service->apply($request);
    }

    public function test_apply_memo_throws_exception_when_memo_not_approved(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::DRAFT,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(10000, 'MYR'),
        );

        $this->memoQuery->method('findById')->willReturn($memo);

        $this->expectException(MemoApplicationException::class);
        $this->expectExceptionMessage('Cannot apply memo in status draft');

        $request = new MemoApplicationRequest(
            memoId: 'memo-1',
            invoiceId: 'invoice-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->service->apply($request);
    }

    public function test_apply_memo_throws_exception_when_vendor_mismatch(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(10000, 'MYR'),
        );

        $invoice = $this->createInvoiceMock(
            id: 'invoice-1',
            vendorId: 'vendor-2', // Different vendor
            balance: Money::of(25000, 'MYR'),
        );

        $this->memoQuery->method('findById')->willReturn($memo);
        $this->invoiceQuery->method('findById')->willReturn($invoice);

        $this->expectException(MemoApplicationException::class);
        $this->expectExceptionMessage('Memo vendor does not match invoice vendor');

        $request = new MemoApplicationRequest(
            memoId: 'memo-1',
            invoiceId: 'invoice-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->service->apply($request);
    }

    public function test_apply_memo_throws_exception_when_amount_exceeds_remaining(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(5000, 'MYR'), // Only 50 remaining
        );

        $invoice = $this->createInvoiceMock(
            id: 'invoice-1',
            vendorId: 'vendor-1',
            balance: Money::of(25000, 'MYR'),
        );

        $this->memoQuery->method('findById')->willReturn($memo);
        $this->invoiceQuery->method('findById')->willReturn($invoice);

        $this->expectException(MemoApplicationException::class);
        $this->expectExceptionMessage('Amount to apply exceeds remaining memo balance');

        $request = new MemoApplicationRequest(
            memoId: 'memo-1',
            invoiceId: 'invoice-1',
            amount: Money::of(10000, 'MYR'), // Trying to apply 100
        );

        $this->service->apply($request);
    }

    public function test_auto_apply_applies_multiple_memos_fifo(): void
    {
        $memo1 = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(5000, 'MYR'),
            remainingAmount: Money::of(5000, 'MYR'),
        );

        $memo2 = $this->createMemoMock(
            id: 'memo-2',
            type: MemoType::CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(10000, 'MYR'),
        );

        $this->memoQuery->method('findUnappliedByVendor')
            ->with('vendor-1')
            ->willReturn([$memo1, $memo2]);

        $invoice = $this->createInvoiceMock(
            id: 'invoice-1',
            vendorId: 'vendor-1',
            balance: Money::of(20000, 'MYR'),
        );

        $this->invoiceQuery->method('findById')->willReturn($invoice);

        $updatedMemo1 = $this->createMemoMock(
            id: 'memo-1',
            type: MemoType::CREDIT,
            status: MemoStatus::APPLIED,
            vendorId: 'vendor-1',
            amount: Money::of(5000, 'MYR'),
            remainingAmount: Money::of(0, 'MYR'),
        );

        $updatedMemo2 = $this->createMemoMock(
            id: 'memo-2',
            type: MemoType::CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            remainingAmount: Money::of(0, 'MYR'),
        );

        $this->memoPersist->method('recordApplication')
            ->willReturnOnConsecutiveCalls($updatedMemo1, $updatedMemo2);

        // Expect two dispatches
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $results = $this->service->autoApplyForVendor('vendor-1', 'invoice-1');

        $this->assertCount(2, $results);
    }

    /**
     * @return MemoInterface&MockObject
     */
    private function createMemoMock(
        string $id,
        MemoType $type,
        MemoStatus $status,
        string $vendorId,
        Money $amount,
        Money $remainingAmount,
    ): MemoInterface&MockObject {
        $memo = $this->createMock(MemoInterface::class);
        $memo->method('getId')->willReturn($id);
        $memo->method('getNumber')->willReturn('CM-2024-001');
        $memo->method('getType')->willReturn($type);
        $memo->method('getStatus')->willReturn($status);
        $memo->method('getVendorId')->willReturn($vendorId);
        $memo->method('getAmount')->willReturn($amount);
        $memo->method('getRemainingAmount')->willReturn($remainingAmount);
        $memo->method('getAppliedAmount')->willReturn(
            Money::of($amount->getAmountInCents() - $remainingAmount->getAmountInCents(), 'MYR')
        );

        return $memo;
    }

    /**
     * @return VendorInvoiceInterface&MockObject
     */
    private function createInvoiceMock(
        string $id,
        string $vendorId,
        Money $balance,
    ): VendorInvoiceInterface&MockObject {
        $invoice = $this->createMock(VendorInvoiceInterface::class);
        $invoice->method('getId')->willReturn($id);
        $invoice->method('getVendorId')->willReturn($vendorId);
        $invoice->method('getBalanceDue')->willReturn($balance);

        return $invoice;
    }
}
