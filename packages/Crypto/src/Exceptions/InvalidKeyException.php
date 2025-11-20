<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Invalid Key Exception
 *
 * Thrown when a cryptographic key is invalid, expired, or corrupted.
 */
class InvalidKeyException extends CryptoException
{
    public static function expired(string $keyId): self
    {
        return new self("Encryption key '{$keyId}' has expired");
    }
    
    public static function notFound(string $keyId): self
    {
        return new self("Encryption key '{$keyId}' not found");
    }
    
    public static function invalidFormat(string $reason): self
    {
        return new self("Invalid key format: {$reason}");
    }
    
    public static function wrongLength(int $expected, int $actual): self
    {
        return new self("Invalid key length: expected {$expected} bytes, got {$actual}");
    }
}
