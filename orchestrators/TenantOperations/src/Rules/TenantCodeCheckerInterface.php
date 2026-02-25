<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

/**
 * Interface for checking tenant code uniqueness.
 */
interface TenantCodeCheckerInterface
{
    public function isCodeUnique(string $tenantCode): bool;
}
