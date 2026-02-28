<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for data exporting.
 */
interface DataExporterInterface
{
    public function export(string $tenantId): string;
}
