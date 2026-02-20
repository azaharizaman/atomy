<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface AuthorizationMatrixPersistInterface
{
    public function save(AuthorizationMatrixInterface $matrix): void;

    public function delete(string $id): void;

    public function deleteByTenantId(string $tenantId): int;

    public function deleteByUserId(string $tenantId, string $userId): int;

    public function deleteByRoleId(string $tenantId, string $roleId): int;
}
