<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for managing tenant state.
 */
interface TenantStateManagerInterface
{
    public function suspend(string $tenantId): void;
    public function activate(string $tenantId): void;
    public function archive(string $tenantId): void;
}
