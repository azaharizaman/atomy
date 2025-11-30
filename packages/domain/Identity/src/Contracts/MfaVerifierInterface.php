<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * MFA verifier interface
 * 
 * Handles multi-factor authentication verification
 */
interface MfaVerifierInterface
{
    /**
     * Verify a TOTP code
     * 
     * @param string $userId User identifier
     * @param string $code TOTP code to verify
     * @return bool True if code is valid
     */
    public function verifyTotp(string $userId, string $code): bool;

    /**
     * Verify a backup code
     * 
     * @param string $userId User identifier
     * @param string $code Backup code to verify
     * @return bool True if code is valid (and consumed)
     */
    public function verifyBackupCode(string $userId, string $code): bool;

    /**
     * Check if user needs MFA verification
     * 
     * @param string $userId User identifier
     * @return bool True if MFA is required
     */
    public function requiresMfa(string $userId): bool;

    /**
     * Mark device as trusted for a user
     * 
     * @param string $userId User identifier
     * @param string $deviceFingerprint Device fingerprint
     * @param int $ttlDays Trust duration in days
     * @return string Trusted device ID
     */
    public function trustDevice(string $userId, string $deviceFingerprint, int $ttlDays = 30): string;

    /**
     * Check if device is trusted
     * 
     * @param string $userId User identifier
     * @param string $deviceFingerprint Device fingerprint
     * @return bool True if device is trusted
     */
    public function isDeviceTrusted(string $userId, string $deviceFingerprint): bool;

    /**
     * Revoke a trusted device
     */
    public function revokeTrustedDevice(string $userId, string $deviceId): void;

    /**
     * Get all trusted devices for a user
     * 
     * @return array<array<string, mixed>> Array of trusted device data
     */
    public function getTrustedDevices(string $userId): array;
}
