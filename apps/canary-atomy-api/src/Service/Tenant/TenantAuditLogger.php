<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use Nexus\TenantOperations\Contracts\AuditLoggerAdapterInterface;
use Nexus\TenantOperations\Services\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class TenantAuditLogger implements AuditLoggerInterface, AuditLoggerAdapterInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function log(string $event, string $tenantId, array $data = []): void {
        $this->logger->info(sprintf('Tenant event "%s" for tenant "%s"', $event, $tenantId), $data);
    }
}
