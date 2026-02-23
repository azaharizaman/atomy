<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationStartResult;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndResult;

/**
 * Service interface for tenant impersonation operations.
 */
interface TenantImpersonationServiceInterface
{
    /**
     * Start an impersonation session.
     */
    public function startImpersonation(ImpersonationStartRequest $request): ImpersonationStartResult;

    /**
     * End an impersonation session.
     */
    public function endImpersonation(ImpersonationEndRequest $request): ImpersonationEndResult;

    /**
     * Check if an impersonation session is active.
     */
    public function isImpersonating(string $adminUserId): bool;

    /**
     * Get the current impersonation session.
     */
    public function getCurrentSession(string $adminUserId): ?ImpersonationStartResult;
}
