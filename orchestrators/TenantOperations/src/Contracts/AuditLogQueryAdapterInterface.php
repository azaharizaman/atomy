<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for querying audit logs.
 */
interface AuditLogQueryAdapterInterface
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
