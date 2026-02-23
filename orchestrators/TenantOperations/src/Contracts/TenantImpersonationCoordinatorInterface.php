<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationStartResult;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndResult;

/**
 * Coordinator interface for tenant impersonation operations.
 */
interface TenantImpersonationCoordinatorInterface extends TenantCoordinatorInterface
{
    /**
     * Start an impersonation session.
     */
    public function startImpersonation(ImpersonationStartRequest $request): ImpersonationStartResult;

    /**
     * End an impersonation session.
     */
    public function endImpersonation(ImpersonationEndRequest $request): ImpersonationEndResult;
}
