<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class SegregationOfDutiesViolationException extends TreasuryException
{
    public static function sameUserCannotApprove(string $userId, string $transactionId): self
    {
        return new self(
            "Segregation of duties violation: User {$userId} cannot approve " .
            "transaction {$transactionId} that they created"
        );
    }

    public static function requiresDifferentApprover(string $transactionId, string $creatorId): self
    {
        return new self(
            "Transaction {$transactionId} requires approval from a different user " .
            "than the creator ({$creatorId})"
        );
    }

    public static function insufficientApprovers(string $transactionId, int $required, int $current): self
    {
        return new self(
            "Transaction {$transactionId} requires {$required} distinct approvers, " .
            "but only {$current} are available"
        );
    }

    public static function sameUserMultipleApprovals(string $userId, string $transactionId): self
    {
        return new self(
            "Segregation of duties violation: User {$userId} has already approved " .
            "transaction {$transactionId}"
        );
    }
}
