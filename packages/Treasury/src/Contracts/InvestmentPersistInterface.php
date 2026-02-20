<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface InvestmentPersistInterface
{
    public function save(InvestmentInterface $investment): void;

    public function delete(string $id): void;

    public function deleteByTenantId(string $tenantId): int;
}
