<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for tenant suspension.
 */
final readonly class TenantSuspendResult
{
    public function __construct(
        public bool $success,
        public ?string $tenantId = null,
        public ?string $message = null,
        public ?string $suspendedAt = null,
    ) {}

    public static function success(string $tenantId, ?string $message = null): self
    {
        return new self(
            success: true,
            tenantId: $tenantId,
            message: $message ?? 'Tenant suspended successfully',
            suspendedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to suspend tenant',
        );
    }
}
