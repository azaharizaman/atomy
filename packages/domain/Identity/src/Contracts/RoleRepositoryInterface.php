<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * Role repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both RoleQueryInterface and RolePersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use RoleQueryInterface for reads and RolePersistInterface for writes
 */
interface RoleRepositoryInterface extends RoleQueryInterface, RolePersistInterface
{
    // All methods inherited from RoleQueryInterface and RolePersistInterface
}

