<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for permission revocation.
 */
final readonly class PermissionRevokeResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $message = null,
    ) {}

    public static function success(string $userId, ?string $message = null): self
    {
        return new self(
            success: true,
            userId: $userId,
            message: $message ?? 'Permission revoked successfully',
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to revoke permission',
        );
    }
}
