<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Interface for managing staff transfer workflows.
 */
interface TransferManagerInterface
{
    /**
     * Create a new transfer request.
     *
     * @param array<string, mixed> $data
     */
    public function createTransferRequest(array $data): TransferInterface;

    /**
     * Approve a transfer request.
     */
    public function approveTransfer(string $transferId, string $approvedBy, string $comment): TransferInterface;

    /**
     * Reject a transfer request.
     */
    public function rejectTransfer(string $transferId, string $rejectedBy, string $reason): TransferInterface;

    /**
     * Cancel a transfer request.
     */
    public function cancelTransfer(string $transferId): bool;

    /**
     * Complete a transfer (execute the actual reassignment).
     */
    public function completeTransfer(string $transferId): TransferInterface;

    /**
     * Rollback a completed transfer.
     */
    public function rollbackTransfer(string $transferId): TransferInterface;

    /**
     * Get transfer request by ID.
     */
    public function getTransfer(string $transferId): ?TransferInterface;

    /**
     * Get pending transfer requests.
     *
     * @return array<TransferInterface>
     */
    public function getPendingTransfers(): array;

    /**
     * Get transfer history for a staff member.
     *
     * @return array<TransferInterface>
     */
    public function getStaffTransferHistory(string $staffId): array;
}
