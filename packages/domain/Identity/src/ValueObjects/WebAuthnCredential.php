<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents a WebAuthn/FIDO2 credential for passwordless authentication.
 *
 * WebAuthn credentials are created during registration and used for
 * authentication via public key cryptography. Each credential tracks
 * usage metrics to detect cloning attempts (sign count rollback).
 *
 * @immutable
 */
final readonly class WebAuthnCredential
{
    /**
     * Create a new WebAuthn credential.
     *
     * @param string $credentialId The unique credential identifier (Base64URL encoded)
     * @param string $publicKey The public key in COSE format (Base64 encoded)
     * @param int $signCount The signature counter (increments on each use)
     * @param array<string> $transports The supported transport methods (usb, nfc, ble, internal)
     * @param string|null $aaguid The Authenticator Attestation GUID (identifies authenticator model)
     * @param string|null $lastUsedDeviceFingerprint The device fingerprint from last use
     * @param string|null $friendlyName User-assigned name for the credential
     * @param DateTimeImmutable|null $lastUsedAt When the credential was last used
     * @throws InvalidArgumentException If any parameter is invalid
     */
    public function __construct(
        public string $credentialId,
        public string $publicKey,
        public int $signCount,
        public array $transports,
        public ?string $aaguid = null,
        public ?string $lastUsedDeviceFingerprint = null,
        public ?string $friendlyName = null,
        public ?DateTimeImmutable $lastUsedAt = null,
    ) {
        $this->validateCredentialId($credentialId);
        $this->validatePublicKey($publicKey);
        $this->validateSignCount($signCount);
        $this->validateTransports($transports);
        
        if ($aaguid !== null) {
            $this->validateAaguid($aaguid);
        }
        
        if ($friendlyName !== null) {
            $this->validateFriendlyName($friendlyName);
        }
    }

    /**
     * Validate the credential ID format.
     *
     * @throws InvalidArgumentException If credential ID is invalid
     */
    private function validateCredentialId(string $credentialId): void
    {
        if (empty($credentialId)) {
            throw new InvalidArgumentException('Credential ID cannot be empty');
        }

        // Credential IDs should be Base64URL encoded
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $credentialId)) {
            throw new InvalidArgumentException('Credential ID must be Base64URL encoded');
        }

        // Typical credential IDs are at least 16 bytes (22 chars in Base64URL)
        if (strlen($credentialId) < 22) {
            throw new InvalidArgumentException('Credential ID must be at least 22 characters long');
        }
    }

    /**
     * Validate the public key format.
     *
     * @throws InvalidArgumentException If public key is invalid
     */
    private function validatePublicKey(string $publicKey): void
    {
        if (empty($publicKey)) {
            throw new InvalidArgumentException('Public key cannot be empty');
        }

        // Public keys in COSE format are Base64 encoded
        if (!preg_match('/^[A-Za-z0-9+\/]+=*$/', $publicKey)) {
            throw new InvalidArgumentException('Public key must be Base64 encoded');
        }

        // Minimum reasonable size for a public key
        if (strlen($publicKey) < 44) {  // ~32 bytes encoded
            throw new InvalidArgumentException('Public key must be at least 44 characters long');
        }
    }

    /**
     * Validate the sign count.
     *
     * @throws InvalidArgumentException If sign count is invalid
     */
    private function validateSignCount(int $signCount): void
    {
        if ($signCount < 0) {
            throw new InvalidArgumentException('Sign count cannot be negative');
        }
    }

    /**
     * Validate the transport methods.
     *
     * @param array<string> $transports
     * @throws InvalidArgumentException If transports are invalid
     */
    private function validateTransports(array $transports): void
    {
        $validTransports = ['usb', 'nfc', 'ble', 'internal', 'hybrid'];

        foreach ($transports as $transport) {
            if (!in_array($transport, $validTransports, true)) {
                throw new InvalidArgumentException(
                    "Invalid transport '{$transport}'. Must be one of: " . implode(', ', $validTransports)
                );
            }
        }
    }

    /**
     * Validate the AAGUID format.
     *
     * @throws InvalidArgumentException If AAGUID is invalid
     */
    private function validateAaguid(string $aaguid): void
    {
        // AAGUID should be a UUID (36 chars with hyphens or 32 chars without)
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $compactUuidPattern = '/^[0-9a-f]{32}$/i';

        if (!preg_match($uuidPattern, $aaguid) && !preg_match($compactUuidPattern, $aaguid)) {
            throw new InvalidArgumentException('AAGUID must be a valid UUID format');
        }
    }

    /**
     * Validate the friendly name.
     *
     * @throws InvalidArgumentException If friendly name is invalid
     */
    private function validateFriendlyName(string $friendlyName): void
    {
        $trimmed = trim($friendlyName);
        
        if (empty($trimmed)) {
            throw new InvalidArgumentException('Friendly name cannot be empty or whitespace only');
        }

        if (strlen($trimmed) > 100) {
            throw new InvalidArgumentException('Friendly name cannot exceed 100 characters');
        }
    }

    /**
     * Detect potential sign count rollback (cloning attack).
     *
     * A rollback occurs when the new sign count is less than the stored count,
     * indicating the credential may have been cloned.
     *
     * @param int $newSignCount The sign count from the authentication response
     * @return bool True if rollback detected (security violation)
     */
    public function detectSignCountRollback(int $newSignCount): bool
    {
        // Sign count of 0 indicates authenticator doesn't support counters
        if ($this->signCount === 0 && $newSignCount === 0) {
            return false;
        }

        // Rollback detected: new count is less than stored count
        return $newSignCount < $this->signCount;
    }

    /**
     * Update the credential after successful authentication.
     *
     * @param int $newSignCount The updated signature counter
     * @param string|null $deviceFingerprint The device fingerprint from this authentication
     * @param DateTimeImmutable|null $timestamp When the authentication occurred
     * @return self New instance with updated values
     * @throws InvalidArgumentException If sign count rollback detected
     */
    public function updateAfterAuthentication(
        int $newSignCount,
        ?string $deviceFingerprint = null,
        ?DateTimeImmutable $timestamp = null
    ): self {
        if ($this->detectSignCountRollback($newSignCount)) {
            throw new InvalidArgumentException(
                "Sign count rollback detected: {$newSignCount} < {$this->signCount}. Possible credential cloning."
            );
        }

        return new self(
            credentialId: $this->credentialId,
            publicKey: $this->publicKey,
            signCount: $newSignCount,
            transports: $this->transports,
            aaguid: $this->aaguid,
            lastUsedDeviceFingerprint: $deviceFingerprint ?? $this->lastUsedDeviceFingerprint,
            friendlyName: $this->friendlyName,
            lastUsedAt: $timestamp ?? new DateTimeImmutable(),
        );
    }

    /**
     * Update the friendly name.
     *
     * @throws InvalidArgumentException If friendly name is invalid
     */
    public function withFriendlyName(string $friendlyName): self
    {
        return new self(
            credentialId: $this->credentialId,
            publicKey: $this->publicKey,
            signCount: $this->signCount,
            transports: $this->transports,
            aaguid: $this->aaguid,
            lastUsedDeviceFingerprint: $this->lastUsedDeviceFingerprint,
            friendlyName: $friendlyName,
            lastUsedAt: $this->lastUsedAt,
        );
    }

    /**
     * Check if this credential supports a specific transport.
     */
    public function supportsTransport(string $transport): bool
    {
        return in_array($transport, $this->transports, true);
    }

    /**
     * Check if this is a platform authenticator (built-in).
     */
    public function isPlatformAuthenticator(): bool
    {
        return $this->supportsTransport('internal');
    }

    /**
     * Check if this is a roaming authenticator (external).
     */
    public function isRoamingAuthenticator(): bool
    {
        return !$this->isPlatformAuthenticator();
    }

    /**
     * Get a display name for this credential.
     *
     * Returns the friendly name if set, otherwise generates a default
     * based on the authenticator type and last used date.
     */
    public function getDisplayName(): string
    {
        if ($this->friendlyName !== null) {
            return $this->friendlyName;
        }

        $type = $this->isPlatformAuthenticator() ? 'Platform Authenticator' : 'Security Key';
        
        if ($this->lastUsedAt !== null) {
            return "{$type} (last used {$this->lastUsedAt->format('Y-m-d')})";
        }

        return $type;
    }

    /**
     * Convert to array representation (for storage).
     *
     * @return array{
     *     credential_id: string,
     *     public_key: string,
     *     sign_count: int,
     *     transports: array<string>,
     *     aaguid: string|null,
     *     last_used_device_fingerprint: string|null,
     *     friendly_name: string|null,
     *     last_used_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'credential_id' => $this->credentialId,
            'public_key' => $this->publicKey,
            'sign_count' => $this->signCount,
            'transports' => $this->transports,
            'aaguid' => $this->aaguid,
            'last_used_device_fingerprint' => $this->lastUsedDeviceFingerprint,
            'friendly_name' => $this->friendlyName,
            'last_used_at' => $this->lastUsedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
