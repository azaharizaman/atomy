<?php

declare(strict_types=1);

namespace Nexus\Audit\Exceptions;

/**
 * Thrown when sequence number issues are detected
 * 
 * Satisfies: REL-AUD-0301
 */
class AuditSequenceException extends AuditException
{
    public static function gapDetected(string $tenantId, array $missingSequences): self
    {
        $sequences = implode(', ', $missingSequences);
        return new self(
            "Sequence gaps detected for tenant {$tenantId}: missing sequences {$sequences}"
        );
    }

    public static function duplicateSequence(string $tenantId, int $sequence): self
    {
        return new self(
            "Duplicate sequence number {$sequence} for tenant {$tenantId}"
        );
    }

    public static function outOfOrder(string $tenantId, int $expected, int $actual): self
    {
        return new self(
            "Sequence out of order for tenant {$tenantId}: expected {$expected}, got {$actual}"
        );
    }
}
