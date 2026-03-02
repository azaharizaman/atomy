<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for data archiving.
 */
interface DataArchiverInterface
{
    public function archive(string $tenantId): string;
}
