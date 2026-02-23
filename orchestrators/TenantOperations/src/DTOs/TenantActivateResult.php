<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for tenant activation.
 */
final readonly class TenantActivateResult
{
    public function __construct(
        public bool $success,
        public ?string $tenantId = null,
        public ?string $message = null,
        public ?string $activatedAt = null,
    ) {}

    public static function success(string $tenantId, ?string $message = null): self
    {
        return new self(
            success: true,
            tenantId: $tenantId,
            message: $message ?? 'Tenant activated successfully',
            activatedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to activate tenant',
        );
    }
}
