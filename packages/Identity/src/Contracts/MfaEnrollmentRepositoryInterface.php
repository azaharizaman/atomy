<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\MfaMethod;

/**
 * MFA enrollment repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both MfaEnrollmentQueryInterface and MfaEnrollmentPersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use MfaEnrollmentQueryInterface for reads and MfaEnrollmentPersistInterface for writes
 */
interface MfaEnrollmentRepositoryInterface extends MfaEnrollmentQueryInterface, MfaEnrollmentPersistInterface
{
    // All methods inherited from MfaEnrollmentQueryInterface and MfaEnrollmentPersistInterface
}

