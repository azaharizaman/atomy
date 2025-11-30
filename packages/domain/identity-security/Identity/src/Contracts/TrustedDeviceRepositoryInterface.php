<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Trusted device repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both TrustedDeviceQueryInterface and TrustedDevicePersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use TrustedDeviceQueryInterface for reads and TrustedDevicePersistInterface for writes
 */
interface TrustedDeviceRepositoryInterface extends TrustedDeviceQueryInterface, TrustedDevicePersistInterface
{
    // All methods inherited from TrustedDeviceQueryInterface and TrustedDevicePersistInterface
}

