<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for controlling user access.
 */
interface UserAccessControllerInterface
{
    public function disable(string $tenantId): void;
    public function enable(string $tenantId): void;
}
