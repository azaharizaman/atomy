<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for audit logging tenant operations.
 * 
 * Must be implemented by Layer 3 (Adapters) using Nexus\AuditLogger package.
 */
interface AuditLoggerAdapterInterface
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
