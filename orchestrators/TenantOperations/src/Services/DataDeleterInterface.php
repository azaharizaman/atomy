<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for data deletion.
 */
interface DataDeleterInterface
{
    public function delete(string $tenantId): void;
}
