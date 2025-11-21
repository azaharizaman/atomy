<?php

declare(strict_types=1);

namespace Nexus\Audit\Exceptions;

/**
 * Thrown when digital signature verification fails
 */
class SignatureVerificationException extends AuditException
{
    public static function invalidSignature(string $recordId): self
    {
        return new self("Invalid digital signature for audit record {$recordId}");
    }

    public static function missingPublicKey(string $signerId): self
    {
        return new self("Public key not found for signer: {$signerId}");
    }
}
