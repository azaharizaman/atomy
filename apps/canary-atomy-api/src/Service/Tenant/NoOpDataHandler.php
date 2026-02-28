<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use Nexus\TenantOperations\Services\DataArchiverInterface;
use Nexus\TenantOperations\Services\DataDeleterInterface;
use Nexus\TenantOperations\Services\DataExporterInterface;

final readonly class NoOpDataHandler implements DataArchiverInterface, DataExporterInterface, DataDeleterInterface
{
    public function archive(string $tenantId): string { return '/tmp/archive/' . $tenantId; }
    public function export(string $tenantId): string { return '/tmp/export/' . $tenantId; }
    public function delete(string $tenantId): void {}
}
