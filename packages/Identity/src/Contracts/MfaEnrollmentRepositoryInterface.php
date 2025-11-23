<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\MfaMethod;

/**
 * Repository contract for MFA enrollment persistence.
 *
 * Handles CRUD operations for MFA enrollments and specialized queries
 * for multi-factor authentication management.
 */
interface MfaEnrollmentRepositoryInterface
{
    /**
     * Find an enrollment by its unique identifier.
     *
     * @param string $enrollmentId The ULID enrollment identifier
     * @return MfaEnrollmentInterface|null The enrollment or null if not found
     */
    public function findById(string $enrollmentId): ?MfaEnrollmentInterface;

    /**
     * Find all enrollments for a specific user.
     *
     * @param string $userId The user identifier
     * @return array<MfaEnrollmentInterface> Array of enrollments
     */
    public function findByUserId(string $userId): array;

    /**
     * Find all active enrollments for a user.
     *
     * Active enrollments are those that are verified and not deleted.
     *
     * @param string $userId The user identifier
     * @return array<MfaEnrollmentInterface> Array of active enrollments
     */
    public function findActiveByUserId(string $userId): array;

    /**
     * Find enrollment by user and method.
     *
     * @param string $userId The user identifier
     * @param MfaMethod $method The MFA method
     * @return MfaEnrollmentInterface|null The enrollment or null if not found
     */
    public function findByUserAndMethod(string $userId, MfaMethod $method): ?MfaEnrollmentInterface;

    /**
     * Find the primary enrollment for a user.
     *
     * The primary enrollment is used as the default MFA method.
     *
     * @param string $userId The user identifier
     * @return MfaEnrollmentInterface|null The primary enrollment or null if none set
     */
    public function findPrimaryByUserId(string $userId): ?MfaEnrollmentInterface;

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
     * Count active enrollments for a user.
     *
     * @param string $userId The user identifier
     * @return int Number of active enrollments
     */
    public function countActiveByUserId(string $userId): int;

    /**
     * Check if user has any verified MFA enrollment.
     *
     * @param string $userId The user identifier
     * @return bool True if user has at least one verified enrollment
     */
    public function hasVerifiedEnrollment(string $userId): bool;

    /**
     * Set an enrollment as primary and unset others.
     *
     * @param string $enrollmentId The enrollment to set as primary
     * @return bool True if successful
     */
    public function setPrimary(string $enrollmentId): bool;

    /**
     * Find all enrollments that need verification reminder.
     *
     * Returns unverified enrollments older than a specified time.
     *
     * @param int $hoursOld Minimum age in hours
     * @return array<MfaEnrollmentInterface> Enrollments needing reminder
     */
    public function findUnverifiedOlderThan(int $hoursOld): array;

    /**
     * Create a new enrollment.
     *
     * @param array $data Enrollment data
     * @return array Created enrollment data
     */
    public function create(array $data): array;

    /**
     * Find pending (unverified) enrollment by user and method.
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return array|null Enrollment data or null
     */
    public function findPendingByUserAndMethod(string $userId, string $method): ?array;

    /**
     * Find active enrollment by user and method.
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return array|null Enrollment data or null
     */
    public function findActiveByUserAndMethod(string $userId, string $method): ?array;

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
     * Find active backup codes for a user.
     *
     * @param string $userId User identifier
     * @return array Array of backup code enrollments
     */
    public function findActiveBackupCodes(string $userId): array;

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
