<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class SegregationOfDutiesViolationException extends TreasuryException
{
    private static function maskId(string $id): string
    {
        $length = strlen($id);
        if ($length <= 4) {
            return '****';
        }
        if ($length <= 12) {
            return '****';
        }
        $keep = max(1, (int) ($length * 0.25));
        return substr($id, 0, $keep) . str_repeat('*', $length - ($keep * 2)) . substr($id, -$keep);
    }

    public static function sameUserCannotApprove(string $userId, string $transactionId): self
    {
        $maskedUserId = self::maskId($userId);
        $maskedTransactionId = self::maskId($transactionId);
        return new self(
            "Segregation of duties violation: User {$maskedUserId} cannot approve " .
            "transaction {$maskedTransactionId} that they created"
        );
    }

    public static function requiresDifferentApprover(string $transactionId, string $creatorId): self
    {
        $maskedTransactionId = self::maskId($transactionId);
        $maskedCreatorId = self::maskId($creatorId);
        return new self(
            "Transaction {$maskedTransactionId} requires approval from a different user " .
            "than the creator ({$maskedCreatorId})"
        );
    }

    public static function insufficientApprovers(string $transactionId, int $required, int $current): self
    {
        $maskedTransactionId = self::maskId($transactionId);
        return new self(
            "Transaction {$maskedTransactionId} requires {$required} distinct approvers, " .
            "but only {$current} are available"
        );
    }

    public static function sameUserMultipleApprovals(string $userId, string $transactionId): self
    {
        $maskedUserId = self::maskId($userId);
        $maskedTransactionId = self::maskId($transactionId);
        return new self(
            "Segregation of duties violation: User {$maskedUserId} has already approved " .
            "transaction {$maskedTransactionId}"
        );
    }
}
