<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\ProcurementOperations\Contracts\MemoInterface;
use Nexus\ProcurementOperations\Contracts\MemoPersistInterface;
use Nexus\ProcurementOperations\Contracts\MemoQueryInterface;
use Nexus\ProcurementOperations\DTOs\MemoRequest;
use Nexus\ProcurementOperations\DTOs\MemoResult;
use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Events\MemoApprovedEvent;
use Nexus\ProcurementOperations\Events\MemoCreatedEvent;
use Nexus\ProcurementOperations\Exceptions\MemoNotFoundException;
use Nexus\ProcurementOperations\Exceptions\MemoProcessingException;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates credit/debit memo lifecycle.
 *
 * Responsibilities:
 * - Create memos with auto-numbering
 * - Route memos for approval based on reason
 * - Approve/reject memos
 * - Publish events for side effects
 */
final readonly class MemoCoordinator
{
    public function __construct(
        private MemoQueryInterface $memoQuery,
        private MemoPersistInterface $memoPersist,
        private SequencingManagerInterface $sequencing,
        private EventDispatcherInterface $eventDispatcher,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Create a new credit or debit memo.
     */
    public function createMemo(MemoRequest $request): MemoResult
    {
        $this->logger->info('Creating memo', [
            'type' => $request->type->value,
            'reason' => $request->reason->value,
            'vendor_id' => $request->vendorId,
            'amount' => $request->amount->getAmountInCents(),
        ]);

        try {
            // Generate memo number
            $memoNumber = $this->generateMemoNumber($request);

            // Create memo with generated number
            $requestWithNumber = new MemoRequest(
                type: $request->type,
                reason: $request->reason,
                vendorId: $request->vendorId,
                amount: $request->amount,
                description: $request->description,
                invoiceId: $request->invoiceId,
                purchaseOrderId: $request->purchaseOrderId,
                lineItems: $request->lineItems,
                metadata: array_merge($request->metadata, ['number' => $memoNumber]),
            );

            $memo = $this->memoPersist->create($requestWithNumber);

            // Dispatch created event
            $this->eventDispatcher->dispatch(new MemoCreatedEvent(
                memoId: $memo->getId(),
                memoNumber: $memo->getNumber(),
                type: $memo->getType(),
                reason: $memo->getReason(),
                vendorId: $memo->getVendorId(),
                amount: $memo->getAmount(),
                invoiceId: $memo->getInvoiceId(),
                purchaseOrderId: $memo->getPurchaseOrderId(),
                requiresApproval: $request->reason->requiresApproval(),
                occurredAt: new \DateTimeImmutable(),
            ));

            // Log audit
            $this->auditLogger->log(
                entityId: $memo->getId(),
                action: 'memo_created',
                description: sprintf(
                    '%s memo %s created for vendor %s - %s',
                    $memo->getType()->value,
                    $memo->getNumber(),
                    $memo->getVendorId(),
                    $memo->getAmount()->format(),
                ),
            );

            $this->logger->info('Memo created successfully', [
                'memo_id' => $memo->getId(),
                'memo_number' => $memo->getNumber(),
                'requires_approval' => $request->reason->requiresApproval(),
            ]);

            return MemoResult::success(
                memoId: $memo->getId(),
                memoNumber: $memo->getNumber(),
                message: sprintf(
                    '%s memo created successfully%s',
                    $memo->getType()->getLabel(),
                    $request->reason->requiresApproval() ? ' - pending approval' : '',
                ),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create memo', [
                'error' => $e->getMessage(),
                'type' => $request->type->value,
                'vendor_id' => $request->vendorId,
            ]);

            return MemoResult::failure(
                message: 'Failed to create memo: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Approve a memo.
     */
    public function approveMemo(string $memoId, string $approvedBy): MemoResult
    {
        $this->logger->info('Approving memo', [
            'memo_id' => $memoId,
            'approved_by' => $approvedBy,
        ]);

        $memo = $this->memoQuery->findById($memoId);
        if ($memo === null) {
            throw new MemoNotFoundException("Memo {$memoId} not found");
        }

        if ($memo->getStatus() !== MemoStatus::PENDING_APPROVAL) {
            throw new MemoProcessingException(sprintf(
                'Cannot approve memo in status %s - must be %s',
                $memo->getStatus()->value,
                MemoStatus::PENDING_APPROVAL->value,
            ));
        }

        try {
            $approvedAt = new \DateTimeImmutable();
            $updatedMemo = $this->memoPersist->approve($memoId, $approvedBy, $approvedAt);

            // Dispatch approved event
            $this->eventDispatcher->dispatch(new MemoApprovedEvent(
                memoId: $updatedMemo->getId(),
                memoNumber: $updatedMemo->getNumber(),
                type: $updatedMemo->getType(),
                vendorId: $updatedMemo->getVendorId(),
                amount: $updatedMemo->getAmount(),
                approvedBy: $approvedBy,
                approvedAt: $approvedAt,
                occurredAt: $approvedAt,
            ));

            // Log audit
            $this->auditLogger->log(
                entityId: $memoId,
                action: 'memo_approved',
                description: sprintf(
                    'Memo %s approved by %s',
                    $updatedMemo->getNumber(),
                    $approvedBy,
                ),
            );

            $this->logger->info('Memo approved successfully', [
                'memo_id' => $memoId,
                'memo_number' => $updatedMemo->getNumber(),
                'approved_by' => $approvedBy,
            ]);

            return MemoResult::success(
                memoId: $memoId,
                memoNumber: $updatedMemo->getNumber(),
                message: 'Memo approved successfully',
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to approve memo', [
                'memo_id' => $memoId,
                'error' => $e->getMessage(),
            ]);

            return MemoResult::failure(
                message: 'Failed to approve memo: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Reject a memo.
     */
    public function rejectMemo(string $memoId, string $rejectedBy, string $reason): MemoResult
    {
        $this->logger->info('Rejecting memo', [
            'memo_id' => $memoId,
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
        ]);

        $memo = $this->memoQuery->findById($memoId);
        if ($memo === null) {
            throw new MemoNotFoundException("Memo {$memoId} not found");
        }

        if ($memo->getStatus() !== MemoStatus::PENDING_APPROVAL) {
            throw new MemoProcessingException(sprintf(
                'Cannot reject memo in status %s - must be %s',
                $memo->getStatus()->value,
                MemoStatus::PENDING_APPROVAL->value,
            ));
        }

        try {
            $updatedMemo = $this->memoPersist->reject($memoId, $rejectedBy, $reason);

            // Log audit
            $this->auditLogger->log(
                entityId: $memoId,
                action: 'memo_rejected',
                description: sprintf(
                    'Memo %s rejected by %s: %s',
                    $updatedMemo->getNumber(),
                    $rejectedBy,
                    $reason,
                ),
            );

            $this->logger->info('Memo rejected', [
                'memo_id' => $memoId,
                'memo_number' => $updatedMemo->getNumber(),
                'rejected_by' => $rejectedBy,
            ]);

            return MemoResult::success(
                memoId: $memoId,
                memoNumber: $updatedMemo->getNumber(),
                message: 'Memo rejected',
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to reject memo', [
                'memo_id' => $memoId,
                'error' => $e->getMessage(),
            ]);

            return MemoResult::failure(
                message: 'Failed to reject memo: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Cancel a memo.
     */
    public function cancelMemo(string $memoId, string $cancelledBy, string $reason): MemoResult
    {
        $this->logger->info('Cancelling memo', [
            'memo_id' => $memoId,
            'cancelled_by' => $cancelledBy,
        ]);

        $memo = $this->memoQuery->findById($memoId);
        if ($memo === null) {
            throw new MemoNotFoundException("Memo {$memoId} not found");
        }

        if (!$memo->getStatus()->canCancel()) {
            throw new MemoProcessingException(sprintf(
                'Cannot cancel memo in status %s',
                $memo->getStatus()->value,
            ));
        }

        if ($memo->getAppliedAmount()->getAmountInCents() > 0) {
            throw new MemoProcessingException(
                'Cannot cancel memo that has been partially or fully applied',
            );
        }

        try {
            $updatedMemo = $this->memoPersist->cancel($memoId, $cancelledBy, $reason);

            // Log audit
            $this->auditLogger->log(
                entityId: $memoId,
                action: 'memo_cancelled',
                description: sprintf(
                    'Memo %s cancelled by %s: %s',
                    $updatedMemo->getNumber(),
                    $cancelledBy,
                    $reason,
                ),
            );

            $this->logger->info('Memo cancelled', [
                'memo_id' => $memoId,
                'memo_number' => $updatedMemo->getNumber(),
                'cancelled_by' => $cancelledBy,
            ]);

            return MemoResult::success(
                memoId: $memoId,
                memoNumber: $updatedMemo->getNumber(),
                message: 'Memo cancelled',
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to cancel memo', [
                'memo_id' => $memoId,
                'error' => $e->getMessage(),
            ]);

            return MemoResult::failure(
                message: 'Failed to cancel memo: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get memo by ID.
     */
    public function getMemo(string $memoId): MemoInterface
    {
        $memo = $this->memoQuery->findById($memoId);

        if ($memo === null) {
            throw new MemoNotFoundException("Memo {$memoId} not found");
        }

        return $memo;
    }

    /**
     * Get unapplied credit memos for a vendor.
     *
     * @return array<MemoInterface>
     */
    public function getUnappliedMemos(string $vendorId): array
    {
        return $this->memoQuery->findUnappliedByVendor($vendorId);
    }

    /**
     * Generate memo number based on type.
     */
    private function generateMemoNumber(MemoRequest $request): string
    {
        $sequenceName = match ($request->type->value) {
            'credit' => 'credit_memo',
            'debit' => 'debit_memo',
        };

        return $this->sequencing->getNext($sequenceName);
    }
}
