<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Decryption Exception
 *
 * Thrown when decryption operation fails (invalid ciphertext, wrong key, tampered data).
 */
class DecryptionException extends CryptoException
{
    public static function failed(string $reason): self
    {
        return new self("Decryption failed: {$reason}");
    }
    
    public static function invalidCiphertext(): self
    {
        return new self("Invalid ciphertext format");
    }
    
    public static function authenticationFailed(): self
    {
        return new self("Authentication tag verification failed - data may be tampered");
    }
}
