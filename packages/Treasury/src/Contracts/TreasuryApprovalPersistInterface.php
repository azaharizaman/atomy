<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface TreasuryApprovalPersistInterface
{
    public function save(TreasuryApprovalInterface $approval): void;

    public function delete(string $id): void;

    public function deleteByTenantId(string $tenantId): int;
}
