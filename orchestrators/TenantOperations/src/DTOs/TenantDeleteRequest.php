<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for tenant deletion.
 */
final readonly class TenantDeleteRequest
{
    public function __construct(
        public string $tenantId,
        public string $deletedBy,
        public ?string $reason = null,
        public bool $exportData = false,
        public ?array $metadata = null,
    ) {}
}
