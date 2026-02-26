<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * DTO for refresh token payload.
 */
final readonly class RefreshTokenPayload
{
    public function __construct(
        public string $userId,
        public string $tenantId,
        public ?int $exp = null,
        public array $scopes = [],
    ) {}
}
