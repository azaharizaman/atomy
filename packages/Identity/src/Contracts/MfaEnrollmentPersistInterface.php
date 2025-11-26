<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * MFA enrollment persist interface (CQRS Write Model)
 *
 * Handles write operations for MFA enrollments.
 * Consuming applications provide concrete implementations.
 */
interface MfaEnrollmentPersistInterface
{
    /**
     * Save an enrollment (create or update).
     *
     * @param MfaEnrollmentInterface $enrollment The enrollment to save
     * @return MfaEnrollmentInterface The saved enrollment
     */
    public function save(MfaEnrollmentInterface $enrollment): MfaEnrollmentInterface;

    /**
     * Delete an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return bool True if deleted, false if not found
     */
    public function delete(string $enrollmentId): bool;

    /**
     * Set an enrollment as primary and unset others.
     *
     * @param string $enrollmentId The enrollment to set as primary
     * @return bool True if successful
     */
    public function setPrimary(string $enrollmentId): bool;

    /**
     * Activate a pending enrollment.
     *
     * @param string $enrollmentId Enrollment identifier
     * @return bool True if activated
     */
    public function activate(string $enrollmentId): bool;

    /**
     * Revoke an enrollment.
     *
     * @param string $enrollmentId Enrollment identifier
     * @return bool True if revoked
     */
    public function revoke(string $enrollmentId): bool;

    /**
     * Revoke all enrollments by user and method.
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return int Number of enrollments revoked
     */
    public function revokeByUserAndMethod(string $userId, string $method): int;

    /**
     * Revoke all enrollments for a user.
     *
     * @param string $userId User identifier
     * @return int Number of enrollments revoked
     */
    public function revokeAllByUserId(string $userId): int;

    /**
     * Mark a backup code as consumed.
     *
     * @param string $enrollmentId Enrollment identifier
     * @param \DateTimeImmutable $consumedAt Consumption timestamp
     * @return bool True if marked as consumed
     */
    public function consumeBackupCode(string $enrollmentId, \DateTimeImmutable $consumedAt): bool;

    /**
     * Update last used timestamp for an enrollment.
     *
     * @param string $enrollmentId Enrollment identifier
     * @param \DateTimeImmutable $lastUsedAt Last used timestamp
     * @return bool True if updated
     */
    public function updateLastUsed(string $enrollmentId, \DateTimeImmutable $lastUsedAt): bool;
}
