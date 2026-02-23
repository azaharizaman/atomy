<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for permission revocation.
 */
final readonly class PermissionRevokeRequest
{
    public function __construct(
        public string $userId,
        public string $permission,
        public ?string $tenantId = null,
        public string $revokedBy,
    ) {}
}
