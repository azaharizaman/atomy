<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for MFA verification.
 */
final readonly class MfaVerifyRequest
{
    public function __construct(
        public string $userId,
        public string $code,
        public MfaMethod $method,
    ) {}
}
