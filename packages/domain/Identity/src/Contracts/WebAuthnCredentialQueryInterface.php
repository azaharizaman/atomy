<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;

/**
 * WebAuthn credential query interface (CQRS Read Model)
 *
 * Handles read-only operations for WebAuthn credentials.
 * Consuming applications provide concrete implementations.
 */
interface WebAuthnCredentialQueryInterface
{
    /**
     * Find a credential by its credential ID.
     *
     * @param string $credentialId The Base64URL encoded credential ID
     * @return WebAuthnCredential|null The credential or null if not found
     */
    public function findByCredentialId(string $credentialId): ?WebAuthnCredential;

    /**
     * Find all credentials for a user.
     *
     * @param string $userId The user identifier
     * @return array<WebAuthnCredential> Array of credentials
     */
    public function findByUserId(string $userId): array;

    /**
     * Find all credentials for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return array<WebAuthnCredential> Array of credentials
     */
    public function findByEnrollmentId(string $enrollmentId): array;

    /**
     * Count credentials for a user.
     *
     * @param string $userId The user identifier
     * @return int Number of credentials
     */
    public function countByUserId(string $userId): int;

    /**
     * Find credentials by AAGUID.
     *
     * Useful for identifying all credentials from a specific authenticator model.
     *
     * @param string $aaguid The Authenticator Attestation GUID
     * @return array<WebAuthnCredential> Array of credentials
     */
    public function findByAaguid(string $aaguid): array;

    /**
     * Find credentials not used since a given date.
     *
     * Useful for identifying dormant credentials.
     *
     * @param \DateTimeImmutable $since The cutoff date
     * @return array<WebAuthnCredential> Array of dormant credentials
     */
    public function findNotUsedSince(\DateTimeImmutable $since): array;

    /**
     * Find resident keys (discoverable credentials) for a user.
     *
     * @param string $userId User identifier
     * @return array Array of resident key credentials
     */
    public function findResidentKeysByUserId(string $userId): array;
}
