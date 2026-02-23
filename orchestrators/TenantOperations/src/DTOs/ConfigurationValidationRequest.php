<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for configuration validation.
 */
final readonly class ConfigurationValidationRequest
{
    /**
     * @param array<int, string> $requiredConfigs
     */
    public function __construct(
        public string $tenantId,
        public array $requiredConfigs,
        public ?string $requestedBy = null,
        public ?array $metadata = null,
    ) {}
}
