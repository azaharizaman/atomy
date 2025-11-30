<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * User repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both UserQueryInterface and UserPersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use UserQueryInterface for reads and UserPersistInterface for writes
 */
interface UserRepositoryInterface extends UserQueryInterface, UserPersistInterface
{
    // All methods inherited from UserQueryInterface and UserPersistInterface
}

