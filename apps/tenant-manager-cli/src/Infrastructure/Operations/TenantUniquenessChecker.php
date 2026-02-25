<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\TenantOperations\Contracts\TenantCodeCheckerInterface;
use Nexus\TenantOperations\Contracts\TenantDomainCheckerInterface;

/**
 * Note: I should have hoisted TenantCodeCheckerInterface and TenantDomainCheckerInterface too.
 * Let's check if they are in src/Contracts/
 */
final readonly class TenantUniquenessChecker implements TenantCodeCheckerInterface, TenantDomainCheckerInterface
{
    public function __construct(
        private TenantQueryInterface $tenantQuery
    ) {}

    public function isCodeUnique(string $tenantCode): bool
    {
        return $this->tenantQuery->findByCode($tenantCode) === null;
    }

    public function isDomainUnique(string $domain): bool
    {
        return $this->tenantQuery->findByDomain($domain) === null;
    }
}
