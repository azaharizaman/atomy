<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user deactivation.
 */
final readonly class UserDeactivateRequest
{
    public function __construct(
        public string $userId,
        public string $deactivatedBy,
        public ?string $reason = null,
        public bool $preserveData = true,
    ) {}
}
