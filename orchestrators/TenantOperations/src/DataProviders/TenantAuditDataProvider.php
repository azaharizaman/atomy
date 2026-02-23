<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Data provider for tenant audit logs.
 */
final readonly class TenantAuditDataProvider
{
    public function __construct(
        private AuditLogQueryInterface $auditLogQuery,
    ) {}

    /**
     * Get audit logs for a tenant.
     *
     * @return array<int, array{id: string, event: string, tenant_id: string, data: array, created_at: string}>
     */
    public function getAuditLogs(string $tenantId, ?int $limit = 100): array
    {
        return $this->auditLogQuery->getLogsForTenant($tenantId, $limit);
    }

    /**
     * Get audit logs by event type.
     *
     * @return array<int, array{id: string, event: string, tenant_id: string, data: array, created_at: string}>
     */
    public function getAuditLogsByEvent(string $tenantId, string $event, ?int $limit = 100): array
    {
        return $this->auditLogQuery->getLogsByEvent($tenantId, $event, $limit);
    }

    /**
     * Log an audit event.
     */
    public function logEvent(string $tenantId, string $event, array $data): void
    {
        $this->auditLogQuery->log($tenantId, $event, $data);
    }
}

/**
 * Interface for querying audit logs.
 */
interface AuditLogQueryInterface
{
    /**
     * @return array<int, array{id: string, event: string, tenant_id: string, data: array, created_at: string}>
     */
    public function getLogsForTenant(string $tenantId, ?int $limit = 100): array;

    /**
     * @return array<int, array{id: string, event: string, tenant_id: string, data: array, created_at: string}>
     */
    public function getLogsByEvent(string $tenantId, string $event, ?int $limit = 100): array;

    public function log(string $tenantId, string $event, array $data): void;
}
