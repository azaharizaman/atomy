<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user activation.
 */
final readonly class UserActivateRequest
{
    public function __construct(
        public string $userId,
        public string $activatedBy,
        public ?string $reason = null,
    ) {}
}
