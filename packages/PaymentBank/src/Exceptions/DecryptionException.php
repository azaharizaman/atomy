<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Exceptions;

final class DecryptionException extends PaymentBankException
{
    public static function failed(string $reason): self
    {
        return new self("Decryption failed: {$reason}");
    }

    public static function invalidCiphertext(): self
    {
        return new self('The provided ciphertext is invalid or corrupted.');
    }

    public static function keyMismatch(): self
    {
        return new self('The provided key does not match the encrypted data.');
    }
}
