<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * DTO for role details.
 */
final readonly class RoleDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public array $permissions = [],
    ) {}
}
