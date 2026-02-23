<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for tenant activation.
 */
final readonly class TenantActivateRequest
{
    public function __construct(
        public string $tenantId,
        public string $activatedBy,
        public ?string $reason = null,
        public ?array $metadata = null,
    ) {}
}
