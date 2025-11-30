<?php

declare(strict_types=1);

namespace Nexus\Audit\Exceptions;

/**
 * Thrown when audit storage operations fail
 */
class AuditStorageException extends AuditException
{
    public static function insertFailed(string $reason): self
    {
        return new self("Failed to store audit record: {$reason}");
    }

    public static function notFound(string $recordId): self
    {
        return new self("Audit record not found: {$recordId}");
    }
}
