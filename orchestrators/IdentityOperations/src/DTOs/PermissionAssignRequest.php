<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for permission assignment.
 */
final readonly class PermissionAssignRequest
{
    public function __construct(
        public string $userId,
        public string $permission,
        public ?string $tenantId = null,
        public string $assignedBy,
        public ?string $expiresAt = null,
    ) {}
}
