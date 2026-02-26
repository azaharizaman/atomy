<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\Identity\ValueObjects\UserStatus;

/**
 * Interface for authentication.
 */
interface AuthenticatorInterface
{
    /**
     * @return array{id: string, email: string, first_name: string|null, last_name: string|null, status: UserStatus, permissions: array, roles: array}
     */
    public function authenticate(string $email, string $password, ?string $tenantId = null): array;

    /**
     * @return array{id: string, email: string, first_name: string|null, last_name: string|null, status: UserStatus, permissions: array, roles: array}
     */
    public function getUserById(string $userId): array;
}
