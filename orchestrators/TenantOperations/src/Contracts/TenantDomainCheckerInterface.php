<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for checking tenant domain uniqueness.
 */
interface TenantDomainCheckerInterface
{
    public function isDomainUnique(string $domain): bool;
}
