<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use Nexus\Treasury\Enums\TreasuryStatus;

/**
 * Treasury Policy Repository Interface
 */
interface TreasuryPolicyRepositoryInterface
{
    public function findById(string $id): ?TreasuryPolicyInterface;

    public function findByTenantId(string $tenantId): array;

    public function findByStatus(string $tenantId, TreasuryStatus $status): array;

    public function save(TreasuryPolicyInterface $policy): void;

    public function delete(string $id): void;
}
