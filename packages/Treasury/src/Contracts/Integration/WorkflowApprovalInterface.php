<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface WorkflowApprovalInterface
{
    public function submitForApproval(string $workflowType, array $data): string;

    public function getApprovalStatus(string $approvalId): string;

    public function approve(string $approvalId, string $userId, string $comment): bool;

    public function reject(string $approvalId, string $userId, string $reason): bool;

    public function getPendingApprovals(string $userId): array;
}
