<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for tenant validation.
 */
final readonly class TenantValidationRequest
{
    public function __construct(
        public string $tenantId,
        public ?string $requestedBy = null,
        public ?array $metadata = null,
    ) {}
}
