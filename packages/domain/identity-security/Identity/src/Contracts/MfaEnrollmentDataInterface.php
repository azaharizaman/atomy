<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\MfaMethod;

/**
 * MFA enrollment data interface (CQRS Read Model Entity)
 *
 * Represents an MFA enrollment record returned from queries.
 * This is a data transfer interface, not a service interface.
 * Consuming applications provide concrete implementations.
 */
interface MfaEnrollmentDataInterface
{
    /**
     * Get the unique enrollment identifier (ULID)
     */
    public function getId(): string;

    /**
     * Get the user identifier (ULID)
     */
    public function getUserId(): string;

    /**
     * Get the MFA method
     */
    public function getMethod(): MfaMethod;

    /**
     * Get the encrypted secret data
     *
     * Returns array representation of the secret (e.g., TOTP secret parameters)
     * The structure depends on the method type.
     *
     * @return array<string, mixed>
     */
    public function getSecret(): array;

    /**
     * Check if this enrollment is active (verified and not revoked)
     */
    public function isActive(): bool;

    /**
     * Check if this enrollment is the primary MFA method
     */
    public function isPrimary(): bool;

    /**
     * Get when the enrollment was created
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get when the enrollment was last used (null if never used)
     */
    public function getLastUsedAt(): ?\DateTimeImmutable;

    /**
     * Get when the enrollment was verified (null if not verified yet)
     */
    public function getVerifiedAt(): ?\DateTimeImmutable;

    /**
     * Check if this enrollment has been verified
     */
    public function isVerified(): bool;
}
