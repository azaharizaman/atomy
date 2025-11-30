<?php

declare(strict_types=1);

namespace Nexus\Accounting\Exceptions;

/**
 * Exception thrown when consolidation fails.
 */
final class ConsolidationException extends \RuntimeException
{
    public static function invalidEntitySet(array $entityIds): self
    {
        $ids = implode(', ', $entityIds);
        return new self("Invalid entity set for consolidation: {$ids}");
    }

    public static function missingStatements(array $entityIds): self
    {
        $ids = implode(', ', $entityIds);
        return new self(
            "Cannot consolidate: missing statements for entities: {$ids}"
        );
    }

    public static function incompatiblePeriods(string $reason): self
    {
        return new self("Cannot consolidate: incompatible reporting periods - {$reason}");
    }

    public static function eliminationFailed(string $ruleId, \Throwable $previous): self
    {
        return new self(
            "Elimination rule '{$ruleId}' failed: {$previous->getMessage()}",
            0,
            $previous
        );
    }

    public static function unbalancedConsolidation(float $difference): self
    {
        return new self(
            "Consolidated statement is unbalanced. Difference: " . number_format($difference, 2)
        );
    }
}
