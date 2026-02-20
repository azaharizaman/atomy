<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;

interface InvestmentQueryInterface
{
    public function find(string $id): ?InvestmentInterface;

    public function findOrFail(string $id): InvestmentInterface;

    public function findByTenantId(string $tenantId): array;

    public function findByStatus(string $tenantId, InvestmentStatus $status): array;

    public function findActiveByTenantId(string $tenantId): array;

    public function findByType(string $tenantId, InvestmentType $type): array;

    public function findMaturedByTenantId(string $tenantId): array;

    public function findMaturingBetween(
        string $tenantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array;

    public function findByBankAccountId(string $bankAccountId): array;

    public function findByReferenceNumber(string $referenceNumber): ?InvestmentInterface;

    public function exists(string $id): bool;

    public function countByTenantId(string $tenantId): int;

    public function countActiveByTenantId(string $tenantId): int;

    public function sumPrincipalByTenantId(string $tenantId): float;
}
