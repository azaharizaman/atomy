<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for user creation.
 */
final readonly class UserCreateResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $tenantUserId = null,
        public ?string $message = null,
        public ?string $temporaryPassword = null,
    ) {}

    public static function success(
        string $userId,
        ?string $tenantUserId = null,
        ?string $message = null,
        ?string $temporaryPassword = null,
    ): self {
        return new self(
            success: true,
            userId: $userId,
            tenantUserId: $tenantUserId,
            message: $message ?? 'User created successfully',
            temporaryPassword: $temporaryPassword,
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to create user',
        );
    }
}
