<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\BackupCode;
use Nexus\Domain\Identity\ValueObjects\BackupCodeSet;

/**
 * Backup code repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both BackupCodeQueryInterface and BackupCodePersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use BackupCodeQueryInterface for reads and BackupCodePersistInterface for writes
 */
interface BackupCodeRepositoryInterface extends BackupCodeQueryInterface, BackupCodePersistInterface
{
    // All methods inherited from BackupCodeQueryInterface and BackupCodePersistInterface
}

