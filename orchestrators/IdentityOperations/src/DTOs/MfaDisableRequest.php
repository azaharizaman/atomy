<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for MFA disablement.
 */
final readonly class MfaDisableRequest
{
    public function __construct(
        public string $userId,
        public string $disabledBy,
        public ?string $reason = null,
    ) {}
}
