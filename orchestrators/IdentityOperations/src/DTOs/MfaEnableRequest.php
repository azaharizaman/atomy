<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for MFA enablement.
 */
final readonly class MfaEnableRequest
{
    public function __construct(
        public string $userId,
        public MfaMethod $method,
        public ?string $phone = null,
        public ?string $email = null,
    ) {}
}
