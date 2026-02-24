<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for permission assignment.
 */
final readonly class PermissionAssignResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $permissionId = null,
        public ?string $message = null,
    ) {}

    public static function success(string $userId, ?string $permissionId = null, ?string $message = null): self
    {
        return new self(
            success: true,
            userId: $userId,
            permissionId: $permissionId,
            message: $message ?? 'Permission assigned successfully',
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to assign permission',
        );
    }
}
