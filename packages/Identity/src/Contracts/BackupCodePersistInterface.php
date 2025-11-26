<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\BackupCodeSet;

/**
 * Backup code persist interface (CQRS Write Model)
 *
 * Handles write operations for backup codes.
 * Consuming applications provide concrete implementations.
 */
interface BackupCodePersistInterface
{
    /**
     * Save a set of backup codes.
     *
     * Replaces all existing codes for the enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @param BackupCodeSet $codeSet The set of backup codes
     * @return bool True if saved successfully
     */
    public function saveSet(string $enrollmentId, BackupCodeSet $codeSet): bool;

    /**
     * Mark a backup code as consumed.
     *
     * @param string $enrollmentId The enrollment identifier
     * @param string $codeHash The hash of the code to consume
     * @return bool True if marked successfully, false if not found
     */
    public function markAsConsumed(string $enrollmentId, string $codeHash): bool;

    /**
     * Delete all backup codes for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return bool True if deleted
     */
    public function deleteByEnrollmentId(string $enrollmentId): bool;
}
