<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface TenantContextInterface
{
    public function getRequiredTenantId(): string;

    public function getCurrentTenant(): array;

    public function getEntityIds(string $tenantId): array;

    public function isMultiEntity(string $tenantId): bool;
}
