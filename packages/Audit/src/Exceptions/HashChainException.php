<?php

declare(strict_types=1);

namespace Nexus\Audit\Exceptions;

/**
 * Thrown when hash chain calculation or verification fails
 */
class HashChainException extends AuditException
{
    public static function calculationFailed(string $reason): self
    {
        return new self("Hash chain calculation failed: {$reason}");
    }

    public static function previousHashNotFound(string $tenantId, int $sequence): self
    {
        return new self(
            "Cannot link hash chain: previous record not found for tenant {$tenantId}, sequence {$sequence}"
        );
    }
}
