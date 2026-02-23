<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for tenant archiving.
 */
final readonly class TenantArchiveRequest
{
    public function __construct(
        public string $tenantId,
        public string $archivedBy,
        public ?string $reason = null,
        public bool $preserveData = true,
        public ?array $metadata = null,
    ) {}
}
