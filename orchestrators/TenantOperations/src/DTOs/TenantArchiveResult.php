<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for tenant archiving.
 */
final readonly class TenantArchiveResult
{
    public function __construct(
        public bool $success,
        public ?string $tenantId = null,
        public ?string $message = null,
        public ?string $archivedAt = null,
        public ?string $archiveLocation = null,
    ) {}

    public static function success(string $tenantId, ?string $archiveLocation = null, ?string $message = null): self
    {
        return new self(
            success: true,
            tenantId: $tenantId,
            message: $message ?? 'Tenant archived successfully',
            archivedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            archiveLocation: $archiveLocation,
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to archive tenant',
        );
    }
}
