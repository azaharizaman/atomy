<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for user update.
 */
final readonly class UserUpdateResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $message = null,
        public ?string $updatedAt = null,
    ) {}

    public static function success(string $userId, ?string $message = null): self
    {
        return new self(
            success: true,
            userId: $userId,
            message: $message ?? 'User updated successfully',
            updatedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to update user',
        );
    }
}
