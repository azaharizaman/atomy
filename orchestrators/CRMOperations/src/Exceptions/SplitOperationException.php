<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Exceptions;

/**
 * Exception for split-related operations.
 */
class SplitOperationException extends CRMOperationsException
{
    public static function notFound(string $splitId): self
    {
        return new self("Split not found: {$splitId}", "split_id: {$splitId}");
    }

    public static function invalidPercentage(int $totalPercentage): self
    {
        return new self(
            "Split percentages must total 100%. Got: {$totalPercentage}%",
            "total_percentage: {$totalPercentage}"
        );
    }

    public static function missingFields(): self
    {
        return new self(
            'Each split must have user_id and percentage',
            'validation: missing_required_fields'
        );
    }
}
