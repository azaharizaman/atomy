<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\BackupCode;
use Nexus\Domain\Identity\ValueObjects\BackupCodeSet;

/**
 * Backup code query interface (CQRS Read Model)
 *
 * Handles read-only operations for backup codes.
 * Consuming applications provide concrete implementations.
 */
interface BackupCodeQueryInterface
{
    /**
     * Find all backup codes for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return BackupCodeSet The set of backup codes
     */
    public function findByEnrollmentId(string $enrollmentId): BackupCodeSet;

    /**
     * Find a specific backup code by its hash.
     *
     * Used for verification during authentication.
     *
     * @param string $enrollmentId The enrollment identifier
     * @param string $hash The Argon2id hash of the code
     * @return BackupCode|null The code or null if not found
     */
    public function findByHash(string $enrollmentId, string $hash): ?BackupCode;

    /**
     * Count remaining (unconsumed) codes for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return int Number of remaining codes
     */
    public function countRemaining(string $enrollmentId): int;

    /**
     * Check if regeneration should be triggered.
     *
     * Returns true if remaining codes are below threshold (typically ≤2).
     *
     * @param string $enrollmentId The enrollment identifier
     * @param int $threshold The threshold for triggering regeneration
     * @return bool True if regeneration should be triggered
     */
    public function shouldTriggerRegeneration(string $enrollmentId, int $threshold = 2): bool;
}
