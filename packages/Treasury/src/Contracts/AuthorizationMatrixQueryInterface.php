<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

interface AuthorizationMatrixQueryInterface
{
    public function find(string $id): ?AuthorizationMatrixInterface;

    public function findOrFail(string $id): AuthorizationMatrixInterface;

    public function findByTenantId(string $tenantId): array;

    public function findByUserId(string $tenantId, string $userId): array;

    public function findByRoleId(string $tenantId, string $roleId): array;

    public function findByTransactionType(string $tenantId, string $transactionType): array;

    public function findEffectiveForUser(
        string $tenantId,
        string $userId,
        string $transactionType,
        DateTimeImmutable $date
    ): ?AuthorizationMatrixInterface;

    public function findEffectiveForRole(
        string $tenantId,
        string $roleId,
        string $transactionType,
        DateTimeImmutable $date
    ): ?AuthorizationMatrixInterface;

    public function findHighestLimitForUser(
        string $tenantId,
        string $userId,
        string $transactionType
    ): ?AuthorizationMatrixInterface;

    public function canAuthorize(
        string $tenantId,
        string $userId,
        string $transactionType,
        Money $amount
    ): bool;

    public function exists(string $id): bool;

    public function countByTenantId(string $tenantId): int;
}
