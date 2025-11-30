<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;

/**
 * WebAuthn credential persist interface (CQRS Write Model)
 *
 * Handles write operations for WebAuthn credentials.
 * Consuming applications provide concrete implementations.
 */
interface WebAuthnCredentialPersistInterface
{
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
}
