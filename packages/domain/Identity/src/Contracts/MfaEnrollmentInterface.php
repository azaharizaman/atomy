<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * MFA enrollment interface
 * 
 * Handles multi-factor authentication setup
 */
interface MfaEnrollmentInterface
{
    /**
     * Enroll a user in MFA with TOTP
     * 
     * @param string $userId User identifier
     * @return array{secret: string, qr_code: string} Secret and QR code for enrollment
     */
    public function enrollTotp(string $userId): array;

    /**
     * Verify and confirm TOTP enrollment
     * 
     * @param string $userId User identifier
     * @param string $secret TOTP secret
     * @param string $code Verification code
     * @return bool True if enrollment confirmed
     */
    public function confirmTotpEnrollment(string $userId, string $secret, string $code): bool;

    /**
     * Generate backup codes for a user
     * 
     * @param string $userId User identifier
     * @param int $count Number of backup codes to generate
     * @return string[] Array of backup codes
     */
    public function generateBackupCodes(string $userId, int $count = 10): array;

    /**
     * Disable MFA for a user
     */
    public function disableMfa(string $userId): void;

    /**
     * Check if user has MFA enabled
     */
    public function hasMfaEnabled(string $userId): bool;

    /**
     * Get MFA methods enabled for a user
     * 
     * @return string[] Array of MFA method names (e.g., ["totp", "backup_codes"])
     */
    public function getEnabledMethods(string $userId): array;
}
