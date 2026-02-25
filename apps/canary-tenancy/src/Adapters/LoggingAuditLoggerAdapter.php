<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\AuditLoggerAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingAuditLoggerAdapter implements AuditLoggerAdapterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function log(string $event, string $tenantId, array $data): void
    {
        $this->logger->info('Audit event logged in canary adapter', [
            'event' => $event,
            'tenantId' => $tenantId,
            'data' => $data,
        ]);
    }
}
