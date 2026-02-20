<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use Nexus\Common\ValueObjects\Money;

interface LiquidityPoolQueryInterface
{
    public function find(string $id): ?LiquidityPoolInterface;

    public function findOrFail(string $id): LiquidityPoolInterface;

    public function findByTenantId(string $tenantId): array;

    public function findActiveByTenantId(string $tenantId): array;

    public function findByName(string $tenantId, string $name): ?LiquidityPoolInterface;

    public function findByCurrency(string $tenantId, string $currency): array;

    public function findByBankAccountId(string $bankAccountId): array;

    public function exists(string $id): bool;

    public function hasSufficientLiquidity(string $id, Money $amount): bool;

    public function countByTenantId(string $tenantId): int;
}
