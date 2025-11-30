<?php

declare(strict_types=1);

namespace Nexus\Accounting\Exceptions;

/**
 * Exception thrown when statement version conflict occurs.
 */
final class StatementVersionConflictException extends \RuntimeException
{
    public static function concurrentModification(string $statementId, int $expectedVersion, int $actualVersion): self
    {
        return new self(
            "Statement '{$statementId}' version conflict: expected version {$expectedVersion} but found {$actualVersion}"
        );
    }

    public static function statementLocked(string $statementId): self
    {
        return new self(
            "Statement '{$statementId}' is locked and cannot be modified"
        );
    }

    public static function versionNotFound(string $statementId, int $version): self
    {
        return new self(
            "Statement '{$statementId}' version {$version} not found"
        );
    }

    public static function cannotDeleteLatest(string $statementId): self
    {
        return new self(
            "Cannot delete the latest version of statement '{$statementId}'"
        );
    }
}
