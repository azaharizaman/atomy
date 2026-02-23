<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * User context DTO containing user information and session data.
 */
final readonly class UserContext
{
    public function __construct(
        public ?string $userId,
        public ?string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $tenantId,
        public ?string $status,
        public array $permissions = [],
        public array $roles = [],
        public ?string $sessionId = null,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
    ) {}

    public static function anonymous(): self
    {
        return new self(
            userId: null,
            email: null,
            firstName: null,
            lastName: null,
            tenantId: null,
            status: null,
            permissions: [],
            roles: [],
        );
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}
