<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\MemoInterface;
use Nexus\ProcurementOperations\Contracts\MemoPersistInterface;
use Nexus\ProcurementOperations\Contracts\MemoQueryInterface;
use Nexus\ProcurementOperations\Coordinators\MemoCoordinator;
use Nexus\ProcurementOperations\DTOs\MemoRequest;
use Nexus\ProcurementOperations\Enums\MemoReason;
use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Enums\MemoType;
use Nexus\ProcurementOperations\Events\MemoApprovedEvent;
use Nexus\ProcurementOperations\Events\MemoCreatedEvent;
use Nexus\ProcurementOperations\Exceptions\MemoNotFoundException;
use Nexus\ProcurementOperations\Exceptions\MemoProcessingException;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class MemoCoordinatorTest extends TestCase
{
    private MemoQueryInterface&MockObject $memoQuery;
    private MemoPersistInterface&MockObject $memoPersist;
    private SequencingManagerInterface&MockObject $sequencing;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private AuditLogManagerInterface&MockObject $auditLogger;
    private MemoCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->memoQuery = $this->createMock(MemoQueryInterface::class);
        $this->memoPersist = $this->createMock(MemoPersistInterface::class);
        $this->sequencing = $this->createMock(SequencingManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManagerInterface::class);

        $this->coordinator = new MemoCoordinator(
            memoQuery: $this->memoQuery,
            memoPersist: $this->memoPersist,
            sequencing: $this->sequencing,
            eventDispatcher: $this->eventDispatcher,
            auditLogger: $this->auditLogger,
        );
    }

    public function test_create_memo_generates_number_and_publishes_event(): void
    {
        $this->sequencing->method('getNext')
            ->with('credit_memo')
            ->willReturn('CM-2024-0001');

        $memo = $this->createMemoMock(
            id: 'memo-1',
            number: 'CM-2024-0001',
            type: MemoType::CREDIT,
            reason: MemoReason::PRICE_CORRECTION,
            status: MemoStatus::DRAFT,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->memoPersist->method('create')->willReturn($memo);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(MemoCreatedEvent::class));

        $this->auditLogger->expects($this->once())
            ->method('log');

        $request = new MemoRequest(
            type: MemoType::CREDIT,
            reason: MemoReason::PRICE_CORRECTION,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
            description: 'Price correction for invoice INV-001',
        );

        $result = $this->coordinator->createMemo($request);

        $this->assertTrue($result->success);
        $this->assertSame('memo-1', $result->memoId);
        $this->assertSame('CM-2024-0001', $result->memoNumber);
    }

    public function test_create_debit_memo_uses_correct_sequence(): void
    {
        $this->sequencing->expects($this->once())
            ->method('getNext')
            ->with('debit_memo')
            ->willReturn('DM-2024-0001');

        $memo = $this->createMemoMock(
            id: 'memo-2',
            number: 'DM-2024-0001',
            type: MemoType::DEBIT,
            reason: MemoReason::PRICE_INCREASE,
            status: MemoStatus::PENDING_APPROVAL,
            vendorId: 'vendor-1',
            amount: Money::of(5000, 'MYR'),
        );

        $this->memoPersist->method('create')->willReturn($memo);

        $request = new MemoRequest(
            type: MemoType::DEBIT,
            reason: MemoReason::PRICE_INCREASE,
            vendorId: 'vendor-1',
            amount: Money::of(5000, 'MYR'),
            description: 'Price increase per vendor notification',
        );

        $result = $this->coordinator->createMemo($request);

        $this->assertTrue($result->success);
    }

    public function test_approve_memo_publishes_approved_event(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            number: 'CM-2024-0001',
            type: MemoType::CREDIT,
            reason: MemoReason::GOODWILL_CREDIT,
            status: MemoStatus::PENDING_APPROVAL,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->memoQuery->method('findById')
            ->with('memo-1')
            ->willReturn($memo);

        $approvedMemo = $this->createMemoMock(
            id: 'memo-1',
            number: 'CM-2024-0001',
            type: MemoType::CREDIT,
            reason: MemoReason::GOODWILL_CREDIT,
            status: MemoStatus::APPROVED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->memoPersist->method('approve')->willReturn($approvedMemo);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(MemoApprovedEvent::class));

        $result = $this->coordinator->approveMemo('memo-1', 'approver-1');

        $this->assertTrue($result->success);
        $this->assertStringContainsString('approved', strtolower($result->message));
    }

    public function test_approve_memo_throws_exception_when_not_found(): void
    {
        $this->memoQuery->method('findById')->willReturn(null);

        $this->expectException(MemoNotFoundException::class);

        $this->coordinator->approveMemo('non-existent', 'approver-1');
    }

    public function test_approve_memo_throws_exception_when_not_pending(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            number: 'CM-2024-0001',
            type: MemoType::CREDIT,
            reason: MemoReason::PRICE_CORRECTION,
            status: MemoStatus::DRAFT, // Not pending approval
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->memoQuery->method('findById')->willReturn($memo);

        $this->expectException(MemoProcessingException::class);
        $this->expectExceptionMessage('Cannot approve memo in status draft');

        $this->coordinator->approveMemo('memo-1', 'approver-1');
    }

    public function test_reject_memo_logs_audit(): void
    {
        $memo = $this->createMemoMock(
            id: 'memo-1',
            number: 'CM-2024-0001',
            type: MemoType::CREDIT,
            reason: MemoReason::GOODWILL_CREDIT,
            status: MemoStatus::PENDING_APPROVAL,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->memoQuery->method('findById')->willReturn($memo);

        $rejectedMemo = $this->createMemoMock(
            id: 'memo-1',
            number: 'CM-2024-0001',
            type: MemoType::CREDIT,
            reason: MemoReason::GOODWILL_CREDIT,
            status: MemoStatus::REJECTED,
            vendorId: 'vendor-1',
            amount: Money::of(10000, 'MYR'),
        );

        $this->memoPersist->method('reject')->willReturn($rejectedMemo);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with(
                'memo-1',
                'memo_rejected',
                $this->stringContains('rejected'),
            );

        $result = $this->coordinator->rejectMemo('memo-1', 'rejector-1', 'Amount too high');

        $this->assertTrue($result->success);
    }

    public function test_cancel_memo_throws_exception_when_already_applied(): void
    {
        $memo = $this->createMock(MemoInterface::class);
        $memo->method('getId')->willReturn('memo-1');
        $memo->method('getStatus')->willReturn(MemoStatus::APPROVED);
        $memo->method('getAppliedAmount')->willReturn(Money::of(5000, 'MYR')); // Partially applied

        $this->memoQuery->method('findById')->willReturn($memo);

        $this->expectException(MemoProcessingException::class);
        $this->expectExceptionMessage('Cannot cancel memo that has been partially or fully applied');

        $this->coordinator->cancelMemo('memo-1', 'canceller-1', 'No longer needed');
    }

    public function test_get_unapplied_memos_delegates_to_query(): void
    {
        $memos = [
            $this->createMemoMock(
                id: 'memo-1',
                number: 'CM-2024-0001',
                type: MemoType::CREDIT,
                reason: MemoReason::PRICE_CORRECTION,
                status: MemoStatus::APPROVED,
                vendorId: 'vendor-1',
                amount: Money::of(10000, 'MYR'),
            ),
        ];

        $this->memoQuery->method('findUnappliedByVendor')
            ->with('vendor-1')
            ->willReturn($memos);

        $result = $this->coordinator->getUnappliedMemos('vendor-1');

        $this->assertCount(1, $result);
    }

    /**
     * @return MemoInterface&MockObject
     */
    private function createMemoMock(
        string $id,
        string $number,
        MemoType $type,
        MemoReason $reason,
        MemoStatus $status,
        string $vendorId,
        Money $amount,
    ): MemoInterface&MockObject {
        $memo = $this->createMock(MemoInterface::class);
        $memo->method('getId')->willReturn($id);
        $memo->method('getNumber')->willReturn($number);
        $memo->method('getType')->willReturn($type);
        $memo->method('getReason')->willReturn($reason);
        $memo->method('getStatus')->willReturn($status);
        $memo->method('getVendorId')->willReturn($vendorId);
        $memo->method('getAmount')->willReturn($amount);
        $memo->method('getInvoiceId')->willReturn(null);
        $memo->method('getPurchaseOrderId')->willReturn(null);
        $memo->method('getAppliedAmount')->willReturn(Money::of(0, 'MYR'));
        $memo->method('getRemainingAmount')->willReturn($amount);

        return $memo;
    }
}
