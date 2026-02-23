<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user update.
 */
final readonly class UserUpdateRequest
{
    public function __construct(
        public string $userId,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phone = null,
        public ?string $locale = null,
        public ?string $timezone = null,
        public ?array $metadata = null,
        public ?string $updatedBy = null,
    ) {}
}
