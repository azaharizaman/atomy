<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for user deactivation.
 */
final readonly class UserDeactivateResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $message = null,
        public ?string $deactivatedAt = null,
    ) {}

    public static function success(string $userId, ?string $message = null): self
    {
        return new self(
            success: true,
            userId: $userId,
            message: $message ?? 'User deactivated successfully',
            deactivatedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to deactivate user',
        );
    }
}
