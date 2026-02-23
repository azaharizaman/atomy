<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantSuspendRequest;
use Nexus\TenantOperations\DTOs\TenantSuspendResult;
use Nexus\TenantOperations\DTOs\TenantActivateRequest;
use Nexus\TenantOperations\DTOs\TenantActivateResult;
use Nexus\TenantOperations\DTOs\TenantArchiveRequest;
use Nexus\TenantOperations\DTOs\TenantArchiveResult;
use Nexus\TenantOperations\DTOs\TenantDeleteRequest;
use Nexus\TenantOperations\DTOs\TenantDeleteResult;

/**
 * Coordinator interface for tenant lifecycle management.
 */
interface TenantLifecycleCoordinatorInterface extends TenantCoordinatorInterface
{
    /**
     * Suspend a tenant.
     */
    public function suspend(TenantSuspendRequest $request): TenantSuspendResult;

    /**
     * Activate a suspended tenant.
     */
    public function activate(TenantActivateRequest $request): TenantActivateResult;

    /**
     * Archive a tenant.
     */
    public function archive(TenantArchiveRequest $request): TenantArchiveResult;

    /**
     * Delete a tenant permanently.
     */
    public function delete(TenantDeleteRequest $request): TenantDeleteResult;
}
