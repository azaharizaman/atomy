<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for managing impersonation sessions.
 */
interface ImpersonationSessionManagerInterface
{
    public function create(
        string $adminUserId,
        string $targetTenantId,
        ?string $reason,
        string $expiresAt,
    ): string;

    public function end(string $sessionId): void;

    public function getActionCount(string $sessionId): int;

    public function recordAction(string $sessionId, string $action): void;

    public function hasActiveSession(string $adminUserId): bool;

    /**
     * @return array{session_id: string, target_tenant_id: string, expires_at: string}|null
     */
    public function getActiveSession(string $adminUserId): ?array;
}
