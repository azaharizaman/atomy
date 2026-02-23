<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for user activation.
 */
final readonly class UserActivateResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $message = null,
        public ?string $activatedAt = null,
    ) {}

    public static function success(string $userId, ?string $message = null): self
    {
        return new self(
            success: true,
            userId: $userId,
            message: $message ?? 'User activated successfully',
            activatedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to activate user',
        );
    }
}
