<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for document retention operations.
 */
final class DocumentRetentionException extends ProcurementOperationsException
{
    /**
     * Create exception for document not found.
     */
    public static function documentNotFound(string $documentId): self
    {
        return new self(
            message: "Document not found: {$documentId}",
            code: 1001
        );
    }

    /**
     * Create exception for document already on hold.
     */
    public static function documentAlreadyOnHold(string $documentId): self
    {
        return new self(
            message: "Document {$documentId} is already under an active legal hold",
            code: 1002
        );
    }

    /**
     * Create exception for no active hold found.
     */
    public static function noActiveHoldFound(string $documentId): self
    {
        return new self(
            message: "No active legal hold found for document: {$documentId}",
            code: 1003
        );
    }

    /**
     * Create exception for document on legal hold (cannot dispose/archive).
     */
    public static function documentOnLegalHold(string $documentId): self
    {
        return new self(
            message: "Cannot perform operation: Document {$documentId} is under legal hold",
            code: 1004
        );
    }

    /**
     * Create exception for invalid retention category.
     */
    public static function invalidRetentionCategory(string $category): self
    {
        return new self(
            message: "Invalid retention category: {$category}",
            code: 1005
        );
    }

    /**
     * Create exception for document still within retention period.
     */
    public static function documentStillUnderRetention(
        string $documentId,
        \DateTimeInterface $expirationDate,
    ): self {
        return new self(
            message: "Document {$documentId} is still within retention period until {$expirationDate->format('Y-m-d')}",
            code: 1006
        );
    }

    /**
     * Create exception for disposal certification failure.
     */
    public static function disposalCertificationFailed(string $documentId, string $reason): self
    {
        return new self(
            message: "Failed to create disposal certification for document {$documentId}: {$reason}",
            code: 1007
        );
    }

    /**
     * Create exception for unauthorized disposal attempt.
     */
    public static function unauthorizedDisposal(string $documentId, string $userId): self
    {
        return new self(
            message: "User {$userId} is not authorized to dispose document {$documentId}",
            code: 1008
        );
    }
}
