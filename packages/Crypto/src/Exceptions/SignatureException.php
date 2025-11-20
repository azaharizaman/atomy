<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Signature Exception
 *
 * Thrown when signature generation or verification fails.
 */
class SignatureException extends CryptoException
{
    public static function generationFailed(string $reason): self
    {
        return new self("Signature generation failed: {$reason}");
    }
    
    public static function verificationFailed(): self
    {
        return new self("Signature verification failed - data may be tampered or key mismatch");
    }
    
    public static function invalidSignature(): self
    {
        return new self("Invalid signature format");
    }
}
