<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\WebAuthnCredential;

/**
 * Repository contract for WebAuthn credential persistence.
 *
 * Specialized repository for FIDO2/WebAuthn credential management.
 */
interface WebAuthnCredentialRepositoryInterface
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
     * Save a credential (create or update).
     *
     * @param string $enrollmentId The enrollment this credential belongs to
     * @param WebAuthnCredential $credential The credential to save
     * @return WebAuthnCredential The saved credential
     */
    public function save(string $enrollmentId, WebAuthnCredential $credential): WebAuthnCredential;

    /**
     * Update credential after successful authentication.
     *
     * Updates the sign count and last used timestamp.
     *
     * @param string $credentialId The credential ID
     * @param int $newSignCount The new sign count
     * @param \DateTimeImmutable $lastUsedAt Last used timestamp
     * @param string|null $deviceFingerprint The device fingerprint
     * @return bool True if updated successfully
     */
    public function updateAfterAuthentication(
        string $credentialId,
        int $newSignCount,
        \DateTimeImmutable $lastUsedAt,
        ?string $deviceFingerprint = null
    ): bool;

    /**
     * Update credential friendly name.
     *
     * @param string $credentialId The credential ID
     * @param string $friendlyName The new friendly name
     * @return bool True if updated successfully
     */
    public function updateFriendlyName(string $credentialId, string $friendlyName): bool;

    /**
     * Delete a credential.
     *
     * @param string $credentialId The credential ID
     * @return bool True if deleted, false if not found
     */
    public function delete(string $credentialId): bool;

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
     * Create a new credential.
     *
     * @param array $data Credential data
     * @return array Created credential data
     */
    public function create(array $data): array;

    /**
     * Revoke a credential.
     *
     * @param string $credentialId Credential ID
     * @return bool True if revoked
     */
    public function revoke(string $credentialId): bool;

    /**
     * Revoke all credentials for a user.
     *
     * @param string $userId User identifier
     * @return int Number of credentials revoked
     */
    public function revokeAllByUserId(string $userId): int;

    /**
     * Find resident keys (discoverable credentials) for a user.
     *
     * @param string $userId User identifier
     * @return array Array of resident key credentials
     */
    public function findResidentKeysByUserId(string $userId): array;
}
