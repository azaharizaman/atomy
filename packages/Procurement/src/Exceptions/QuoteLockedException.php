<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

final class QuoteLockedException extends ProcurementException
{
    public static function cannotModify(string $quoteId, string $comparisonRunId): self
    {
        return new self(sprintf(
            "Cannot modify vendor quote '%s': locked by comparison run '%s'.",
            $quoteId,
            $comparisonRunId,
        ));
    }

    public static function alreadyLocked(string $quoteId, string $existingRunId): self
    {
        return new self(sprintf(
            "Vendor quote '%s' is already locked by comparison run '%s'.",
            $quoteId,
            $existingRunId,
        ));
    }

    public static function lockMismatch(string $quoteId, string $expectedRunId, ?string $actualRunId): self
    {
        return new self(sprintf(
            "Cannot unlock vendor quote '%s': expected run '%s' but locked by '%s'.",
            $quoteId,
            $expectedRunId,
            $actualRunId ?? 'none',
        ));
    }
}
