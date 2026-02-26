<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

/**
 * Interface for querying user data.
 */
interface UserQueryInterface
{
    /**
     * @return array{id: string, email: string, first_name: string, last_name: string, tenant_id: string|null, status: string}|null
     */
    public function findById(string $userId): ?array;

    public function exists(string $userId): bool;

    public function isActive(string $userId): bool;
}
