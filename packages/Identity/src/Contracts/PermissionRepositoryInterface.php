<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Permission repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both PermissionQueryInterface and PermissionPersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use PermissionQueryInterface for reads and PermissionPersistInterface for writes
 */
interface PermissionRepositoryInterface extends PermissionQueryInterface, PermissionPersistInterface
{
    // All methods inherited from PermissionQueryInterface and PermissionPersistInterface
}

