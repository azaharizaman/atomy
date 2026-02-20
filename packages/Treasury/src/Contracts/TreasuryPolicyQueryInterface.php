<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;

interface TreasuryPolicyQueryInterface
{
    public function find(string $id): ?TreasuryPolicyInterface;

    public function findOrFail(string $id): TreasuryPolicyInterface;

    public function findByTenantId(string $tenantId): array;

    public function findActiveByTenantId(string $tenantId): array;

    public function findByName(string $tenantId, string $name): ?TreasuryPolicyInterface;

    public function findEffectiveForDate(string $tenantId, DateTimeImmutable $date): ?TreasuryPolicyInterface;

    public function exists(string $id): bool;

    public function countByTenantId(string $tenantId): int;
}
