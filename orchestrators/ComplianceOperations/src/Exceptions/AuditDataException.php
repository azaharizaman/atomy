<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Exceptions;

use RuntimeException;

/**
 * Exception for audit data operations.
 */
final class AuditDataException extends RuntimeException
{
    public static function recordNotFound(string $recordId): self
    {
        return new self("Audit record not found: {$recordId}");
    }

    public static function integrityCheckFailed(string $entityId): self
    {
        return new self("Audit integrity check failed for entity: {$entityId}");
    }

    public static function searchFailed(string $reason): self
    {
        return new self("Audit search failed: {$reason}");
    }
}
