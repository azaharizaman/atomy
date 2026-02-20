<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;

interface IntercompanyLoanQueryInterface
{
    public function find(string $id): ?IntercompanyTreasuryInterface;

    public function findOrFail(string $id): IntercompanyTreasuryInterface;

    public function findByTenantId(string $tenantId): array;

    public function findByFromEntity(string $entityId): array;

    public function findByToEntity(string $entityId): array;

    public function findBetweenEntities(string $fromEntityId, string $toEntityId): array;

    public function findActiveByTenantId(string $tenantId): array;

    public function findOverdueByTenantId(string $tenantId): array;

    public function findByReferenceNumber(string $referenceNumber): ?IntercompanyTreasuryInterface;

    public function exists(string $id): bool;

    public function countByTenantId(string $tenantId): int;

    public function countActiveByTenantId(string $tenantId): int;

    public function sumOutstandingByTenantId(string $tenantId): float;
}
