<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

/**
 * Authorization Limit Repository Interface
 */
interface AuthorizationLimitRepositoryInterface
{
    public function findById(string $id): ?AuthorizationLimitInterface;

    public function findByUserId(string $userId): array;

    public function findByRoleId(string $roleId): array;

    public function findActiveByAmount(string $tenantId, float $amount, string $currency): ?AuthorizationLimitInterface;

    public function save(AuthorizationLimitInterface $limit): void;

    public function delete(string $id): void;
}
