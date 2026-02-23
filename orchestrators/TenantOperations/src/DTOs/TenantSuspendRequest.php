<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for tenant suspension.
 */
final readonly class TenantSuspendRequest
{
    public function __construct(
        public string $tenantId,
        public string $suspendedBy,
        public ?string $reason = null,
        public ?array $metadata = null,
    ) {}
}
