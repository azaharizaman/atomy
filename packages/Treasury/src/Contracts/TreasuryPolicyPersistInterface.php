<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface TreasuryPolicyPersistInterface
{
    public function save(TreasuryPolicyInterface $policy): void;

    public function delete(string $id): void;

    public function deleteByTenantId(string $tenantId): int;
}
