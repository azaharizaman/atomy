<?php

declare(strict_types=1);

namespace Nexus\Accounting\Exceptions;

/**
 * Exception thrown when statement generation fails.
 */
final class StatementGenerationException extends \RuntimeException
{
    public static function forType(string $type, string $reason): self
    {
        return new self("Failed to generate {$type} statement: {$reason}");
    }

    public static function missingData(string $type, string $missingElement): self
    {
        return new self(
            "Cannot generate {$type} statement: missing required data '{$missingElement}'"
        );
    }

    public static function invalidPeriod(string $type, string $periodId): self
    {
        return new self(
            "Cannot generate {$type} statement: invalid period '{$periodId}'"
        );
    }

    public static function ledgerError(string $type, \Throwable $previous): self
    {
        return new self(
            "Failed to generate {$type} statement due to ledger query error: {$previous->getMessage()}",
            0,
            $previous
        );
    }
}
