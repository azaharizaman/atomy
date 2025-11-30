<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Repository interface for Transfer persistence operations.
 */
interface TransferRepositoryInterface
{
    public function findById(string $id): ?TransferInterface;

    /**
     * @return array<TransferInterface>
     */
    public function getByStaff(string $staffId): array;

    /**
     * @return array<TransferInterface>
     */
    public function getPendingTransfers(): array;

    /**
     * @return array<TransferInterface>
     */
    public function getPendingByStaff(string $staffId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): TransferInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): TransferInterface;

    public function delete(string $id): bool;

    public function hasPendingTransfer(string $staffId): bool;

    public function markAsApproved(string $id, string $approvedBy, string $comment): void;

    public function markAsRejected(string $id, string $rejectedBy, string $reason): void;

    public function markAsCompleted(string $id): void;
}
