<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for ending impersonation.
 */
final readonly class ImpersonationEndRequest
{
    public function __construct(
        public string $adminUserId,
        public ?string $sessionId = null,
        public ?string $reason = null,
        public ?array $metadata = null,
    ) {}
}
