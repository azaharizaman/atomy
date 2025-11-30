<?php

declare(strict_types=1);

namespace Nexus\Audit\Exceptions;

/**
 * Thrown when audit record tampering is detected via hash verification
 * 
 * Satisfies: SEC-AUD-0490
 */
class AuditTamperedException extends AuditException
{
    public function __construct(
        string $recordId,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = "Audit record tampering detected for ID {$recordId}: {$reason}";
        parent::__construct($message, $code, $previous);
    }

    public static function hashMismatch(string $recordId, string $expected, string $actual): self
    {
        return new self(
            $recordId,
            "Hash mismatch - expected: {$expected}, actual: {$actual}"
        );
    }

    public static function chainBroken(string $recordId, int $sequence): self
    {
        return new self(
            $recordId,
            "Hash chain broken at sequence {$sequence}"
        );
    }
}
