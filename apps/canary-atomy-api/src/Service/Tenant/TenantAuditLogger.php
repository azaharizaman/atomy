<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use Nexus\TenantOperations\Services\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class TenantAuditLogger implements AuditLoggerInterface
{
    public function __construct(private LoggerInterface $logger) {}
    public function log(string $event, string $tenantId, array $metadata = []): void {
        $this->logger->info(sprintf('Tenant event "%s" for tenant "%s"', $event, $tenantId), $metadata);
    }
}
