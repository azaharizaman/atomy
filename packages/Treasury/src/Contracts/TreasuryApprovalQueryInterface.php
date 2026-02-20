<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Treasury\Enums\ApprovalStatus;

interface TreasuryApprovalQueryInterface
{
    public function find(string $id): ?TreasuryApprovalInterface;

    public function findOrFail(string $id): TreasuryApprovalInterface;

    public function findByTenantId(string $tenantId): array;

    public function findByTransactionId(string $transactionId): ?TreasuryApprovalInterface;

    public function findByStatus(string $tenantId, ApprovalStatus $status): array;

    public function findPendingByTenantId(string $tenantId): array;

    public function findPendingByApprover(string $approverId): array;

    public function findByRequestedBy(string $userId): array;

    public function findExpired(DateTimeImmutable $before): array;

    public function exists(string $id): bool;

    public function countByStatus(string $tenantId, ApprovalStatus $status): int;

    public function countPendingByTenantId(string $tenantId): int;
}
