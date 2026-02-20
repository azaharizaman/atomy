<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class DuplicateApprovalException extends TreasuryException
{
    public static function alreadyApproved(string $approvalId, string $userId): self
    {
        return new self("Approval {$approvalId} has already been processed by user: {$userId}");
    }

    public static function alreadyExists(string $transactionId): self
    {
        return new self("An approval request already exists for transaction: {$transactionId}");
    }

    public static function userAlreadyApproved(string $userId, string $approvalId): self
    {
        return new self("User {$userId} has already approved request: {$approvalId}");
    }

    public static function cannotApproveTwice(string $approvalId): self
    {
        return new self("Cannot approve the same request twice: {$approvalId}");
    }
}
