<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for audit logging tenant operations.
 */
interface AuditLoggerInterface
{
    /**
     * Log an audit event.
     *
     * @param string $event
     * @param string $tenantId
     * @param array<string, mixed> $data
     * @return void
     */
    public function log(string $event, string $tenantId, array $data): void;
}
