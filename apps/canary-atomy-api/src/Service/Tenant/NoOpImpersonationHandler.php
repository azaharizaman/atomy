<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use Nexus\TenantOperations\Services\ImpersonationSessionManagerInterface;
use Nexus\TenantOperations\Services\ImpersonationPermissionCheckerInterface as ServicePermissionChecker;
use Nexus\TenantOperations\Rules\ImpersonationPermissionCheckerInterface as RulePermissionChecker;

final readonly class NoOpImpersonationHandler implements ImpersonationSessionManagerInterface, ServicePermissionChecker, RulePermissionChecker
{
    public function create(string $adminUserId, string $targetTenantId, ?string $reason, string $expiresAt): string
    {
        return 'mock-session-' . uniqid();
    }

    public function end(string $sessionId): void {}

    public function getActionCount(string $sessionId): int { return 0; }

    public function recordAction(string $sessionId, string $action): void {}

    public function hasActiveSession(string $adminUserId): bool { return false; }

    public function getActiveSession(string $adminUserId): ?array { return null; }

    public function hasPermission(string $adminUserId): bool { return true; }

    public function hasImpersonationPermission(string $adminUserId): bool { return true; }
}
