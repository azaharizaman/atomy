<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\MfaMethod;

/**
 * MFA enrollment query interface (CQRS Read Model)
 *
 * Handles read-only operations for MFA enrollments.
 * Consuming applications provide concrete implementations.
 */
interface MfaEnrollmentQueryInterface
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
     * Find all enrollments that need verification reminder.
     *
     * Returns unverified enrollments older than a specified time.
     *
     * @param int $hoursOld Minimum age in hours
     * @return array<MfaEnrollmentInterface> Enrollments needing reminder
     */
    public function findUnverifiedOlderThan(int $hoursOld): array;

    /**
     * Find pending (unverified) enrollment by user and method.
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return MfaEnrollmentInterface|null The pending enrollment or null if not found
     */
    public function findPendingByUserAndMethod(string $userId, string $method): ?MfaEnrollmentInterface;

    /**
     * Find active enrollment by user and method.
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return MfaEnrollmentInterface|null The active enrollment or null if not found
     */
    public function findActiveByUserAndMethod(string $userId, string $method): ?MfaEnrollmentInterface;

    /**
     * Find active backup codes for a user.
     *
     * @param string $userId User identifier
     * @return array<MfaEnrollmentInterface> Array of backup code enrollments
     */
    public function findActiveBackupCodes(string $userId): array;
}
