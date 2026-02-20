<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface IntercompanyLoanPersistInterface
{
    public function save(IntercompanyTreasuryInterface $loan): void;

    public function delete(string $id): void;

    public function deleteByTenantId(string $tenantId): int;
}
