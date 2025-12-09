<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\MemoRequest;

/**
 * Persistence interface for credit/debit memos.
 */
interface MemoPersistInterface
{
    /**
     * Create a new memo.
     */
    public function create(MemoRequest $request): MemoInterface;

    /**
     * Update memo status.
     */
    public function updateStatus(string $memoId, string $status): MemoInterface;

    /**
     * Record memo application to an invoice.
     *
     * @param string $memoId Memo ID
     * @param string $invoiceId Invoice ID
     * @param float $amount Amount applied (in cents)
     */
    public function recordApplication(string $memoId, string $invoiceId, float $amount): MemoInterface;

    /**
     * Approve a memo.
     */
    public function approve(string $memoId, string $approvedBy, \DateTimeImmutable $approvedAt): MemoInterface;

    /**
     * Reject a memo.
     */
    public function reject(string $memoId, string $rejectedBy, string $reason): MemoInterface;

    /**
     * Cancel a memo.
     */
    public function cancel(string $memoId, string $cancelledBy, string $reason): MemoInterface;
}
