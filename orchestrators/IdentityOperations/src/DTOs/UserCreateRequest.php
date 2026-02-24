<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user creation.
 */
final readonly class UserCreateRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public ?string $tenantId = null,
        public array $roles = [],
        public bool $sendWelcomeEmail = true,
        public ?string $phone = null,
        public ?string $locale = null,
        public ?string $timezone = null,
        public ?array $metadata = null,
    ) {}
}
