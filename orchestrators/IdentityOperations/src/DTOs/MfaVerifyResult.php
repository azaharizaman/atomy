<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for MFA verification.
 */
final readonly class MfaVerifyResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $message = null,
        public ?int $remainingAttempts = null,
    ) {}

    public static function success(string $userId, ?string $message = null): self
    {
        return new self(
            success: true,
            userId: $userId,
            message: $message ?? 'MFA verification successful',
        );
    }

    public static function failure(?string $message = null, ?int $remainingAttempts = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'MFA verification failed',
            remainingAttempts: $remainingAttempts,
        );
    }
}
