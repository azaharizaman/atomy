<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for ending impersonation.
 */
final readonly class ImpersonationEndResult
{
    public function __construct(
        public bool $success,
        public ?string $sessionId = null,
        public ?string $adminUserId = null,
        public ?string $message = null,
        public ?string $endedAt = null,
        public ?int $actionsPerformedCount = null,
    ) {}

    public static function success(
        string $sessionId,
        string $adminUserId,
        int $actionsPerformedCount = 0,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            sessionId: $sessionId,
            adminUserId: $adminUserId,
            message: $message ?? 'Impersonation ended successfully',
            endedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            actionsPerformedCount: $actionsPerformedCount,
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to end impersonation',
        );
    }
}
