<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for module validation.
 */
final readonly class ModulesValidationRequest
{
    /**
     * @param array<int, string> $requiredModules
     */
    public function __construct(
        public string $tenantId,
        public array $requiredModules,
        public ?string $requestedBy = null,
        public ?array $metadata = null,
    ) {}
}
