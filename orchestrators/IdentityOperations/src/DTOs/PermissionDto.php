<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * DTO for permission details.
 */
final readonly class PermissionDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
