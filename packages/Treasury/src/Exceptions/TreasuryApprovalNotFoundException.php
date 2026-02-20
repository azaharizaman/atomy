<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class TreasuryApprovalNotFoundException extends TreasuryException
{
    public static function forId(string $id): self
    {
        return new self("Treasury approval not found with ID: {$id}");
    }

    public static function forTransaction(string $transactionId): self
    {
        return new self("No approval found for transaction: {$transactionId}");
    }

    public static function forUser(string $userId, string $approvalId): self
    {
        return new self("Approval {$approvalId} not found or not assigned to user: {$userId}");
    }

    public static function pendingNotFound(string $tenantId): self
    {
        return new self("No pending approvals found for tenant: {$tenantId}");
    }
}
