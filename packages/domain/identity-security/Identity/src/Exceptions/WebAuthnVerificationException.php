<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

use RuntimeException;

/**
 * WebAuthn Verification Exception
 *
 * Thrown when WebAuthn registration or authentication verification fails.
 */
final class WebAuthnVerificationException extends RuntimeException
{
    public static function invalidCredentialFormat(string $reason): self
    {
        return new self("Invalid credential format: {$reason}");
    }

    public static function challengeMismatch(): self
    {
        return new self('Challenge mismatch');
    }

    public static function originMismatch(string $expected, string $actual): self
    {
        return new self("Origin mismatch: expected '{$expected}', got '{$actual}'");
    }

    public static function invalidSignature(): self
    {
        return new self('Invalid signature');
    }

    public static function attestationVerificationFailed(string $reason): self
    {
        return new self("Attestation verification failed: {$reason}");
    }

    public static function userNotPresent(): self
    {
        return new self('User presence flag not set');
    }

    public static function userNotVerified(): self
    {
        return new self('User verification required but not performed');
    }
}
