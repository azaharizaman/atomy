<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user suspension.
 */
final readonly class UserSuspendRequest
{
    public function __construct(
        public string $userId, public string $tenantId,
        public string $suspendedBy,
        public ?string $reason = null,
        public ?array $metadata = null,
    ) {}
}
