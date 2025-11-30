<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Encryption Exception
 *
 * Thrown when encryption operation fails.
 */
class EncryptionException extends CryptoException
{
    public static function failed(string $reason): self
    {
        return new self("Encryption failed: {$reason}");
    }
}
