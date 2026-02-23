<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for starting impersonation.
 */
final readonly class ImpersonationStartRequest
{
    public function __construct(
        public string $adminUserId,
        public string $targetTenantId,
        public ?string $reason = null,
        public ?int $sessionTimeoutMinutes = null,
        public ?array $metadata = null,
    ) {}
}
