<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result DTO for MFA enablement.
 */
final readonly class MfaEnableResult
{
    public function __construct(
        public bool $success,
        public ?string $userId = null,
        public ?string $message = null,
        public ?string $secret = null,
        public ?string $qrCodeUrl = null,
        public ?array $backupCodes = null,
    ) {}

    public static function success(
        string $userId,
        ?string $secret = null,
        ?string $qrCodeUrl = null,
        ?array $backupCodes = null,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            userId: $userId,
            message: $message ?? 'MFA enabled successfully',
            secret: $secret,
            qrCodeUrl: $qrCodeUrl,
            backupCodes: $backupCodes,
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to enable MFA',
        );
    }
}
