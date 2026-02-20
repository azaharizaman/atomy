<?php

declare(strict_types=1);

namespace Nexus\Treasury\Repositories;

use Nexus\Treasury\Contracts\TreasuryApprovalInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalRepositoryInterface;
use Nexus\Treasury\Enums\ApprovalStatus;

final class InMemoryTreasuryApprovalRepository implements TreasuryApprovalRepositoryInterface
{
    /** @var array<string, TreasuryApprovalInterface> */
    private array $approvals = [];

    public function findById(string $id): ?TreasuryApprovalInterface
    {
        return $this->approvals[$id] ?? null;
    }

    public function findByTenantId(string $tenantId): array
    {
        return array_values(
            array_filter(
                $this->approvals,
                fn(TreasuryApprovalInterface $approval) => $approval->getTenantId() === $tenantId
            )
        );
    }

    public function findByStatus(string $tenantId, ApprovalStatus $status): array
    {
        return array_values(
            array_filter(
                $this->approvals,
                fn(TreasuryApprovalInterface $approval) => 
                    $approval->getTenantId() === $tenantId && $approval->getStatus() === $status
            )
        );
    }

    public function findPendingByUserId(string $userId): array
    {
        return array_values(
            array_filter(
                $this->approvals,
                fn(TreasuryApprovalInterface $approval) => 
                    $approval->getSubmittedBy() === $userId &&
                    $approval->getStatus() === ApprovalStatus::PENDING
            )
        );
    }

    public function save(TreasuryApprovalInterface $approval): void
    {
        $this->approvals[$approval->getId()] = $approval;
    }

    public function delete(string $id): void
    {
        unset($this->approvals[$id]);
    }
}
