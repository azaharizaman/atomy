<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\TenantOperations\Contracts\AuditLoggerAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class AuditLoggerAdapter implements AuditLoggerAdapterInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function log(string $event, string $tenantId, array $data): void
    {
        $this->logger->info("Audit Log: $event", ['tenantId' => $tenantId, 'data' => $data]);
    }
}
