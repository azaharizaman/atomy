<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * MFA method value object
 * 
 * Represents a multi-factor authentication method
 */
enum MfaMethod: string
{
    case PASSKEY = 'passkey';
    case TOTP = 'totp';
    case SMS = 'sms';
    case EMAIL = 'email';
    case BACKUP_CODES = 'backup_codes';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::PASSKEY => 'Passkey (Biometric)',
            self::TOTP => 'Authenticator App (TOTP)',
            self::SMS => 'SMS',
            self::EMAIL => 'Email',
            self::BACKUP_CODES => 'Backup Codes',
        };
    }

    /**
     * Get icon identifier for UI
     */
    public function icon(): string
    {
        return match($this) {
            self::PASSKEY => 'fingerprint',
            self::TOTP => 'smartphone',
            self::SMS => 'message',
            self::EMAIL => 'mail',
            self::BACKUP_CODES => 'key',
        };
    }

    /**
     * Check if this method requires enrollment
     */
    public function requiresEnrollment(): bool
    {
        return match($this) {
            self::PASSKEY => true,
            self::TOTP => true,
            self::SMS => true,
            self::EMAIL => true,
            self::BACKUP_CODES => true,
        };
    }

    /**
     * Check if this method can be used as primary authentication factor
     */
    public function canBePrimary(): bool
    {
        return match($this) {
            self::PASSKEY => true,  // Passwordless capable
            self::TOTP => true,
            self::BACKUP_CODES => false,  // Backup only
            default => false,  // SMS/Email not primary in this implementation
        };
    }

    /**
     * Check if this method enables passwordless authentication
     */
    public function isPasswordless(): bool
    {
        return match($this) {
            self::PASSKEY => true,  // Only Passkey is truly passwordless
            default => false,
        };
    }
}
