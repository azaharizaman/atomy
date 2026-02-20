<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use Nexus\Treasury\Enums\ApprovalStatus;

/**
 * Treasury Approval Repository Interface
 */
interface TreasuryApprovalRepositoryInterface
{
    public function findById(string $id): ?TreasuryApprovalInterface;

    public function findByTenantId(string $tenantId): array;

    public function findByStatus(string $tenantId, ApprovalStatus $status): array;

    public function findPendingByUserId(string $userId): array;

    public function save(TreasuryApprovalInterface $approval): void;

    /**
     * Delete a treasury approval by its ID.
     */
    public function delete(string $id): void;
}
