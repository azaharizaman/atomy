<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for tenant validation.
 */
final readonly class TenantValidationResult
{
    /**
     * @param array<int, array{rule: string, message: string, severity: string}> $errors
     */
    public function __construct(
        public bool $valid,
        public ?string $tenantId = null,
        public array $errors = [],
        public ?string $message = null,
    ) {}

    public static function valid(string $tenantId, ?string $message = null): self
    {
        return new self(
            valid: true,
            tenantId: $tenantId,
            message: $message ?? 'Tenant validation passed',
        );
    }

    /**
     * @param array<int, array{rule: string, message: string, severity: string}> $errors
     */
    public static function invalid(string $tenantId, array $errors, ?string $message = null): self
    {
        return new self(
            valid: false,
            tenantId: $tenantId,
            errors: $errors,
            message: $message ?? 'Tenant validation failed',
        );
    }
}
