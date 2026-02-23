<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for starting impersonation.
 */
final readonly class ImpersonationStartResult
{
    public function __construct(
        public bool $success,
        public ?string $sessionId = null,
        public ?string $adminUserId = null,
        public ?string $targetTenantId = null,
        public ?string $message = null,
        public ?string $startedAt = null,
        public ?string $expiresAt = null,
    ) {}

    public static function success(
        string $sessionId,
        string $adminUserId,
        string $targetTenantId,
        string $expiresAt,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            sessionId: $sessionId,
            adminUserId: $adminUserId,
            targetTenantId: $targetTenantId,
            message: $message ?? 'Impersonation started successfully',
            startedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            expiresAt: $expiresAt,
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to start impersonation',
        );
    }
}
