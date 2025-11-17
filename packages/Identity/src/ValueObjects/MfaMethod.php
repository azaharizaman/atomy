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
            self::TOTP => 'Authenticator App (TOTP)',
            self::SMS => 'SMS',
            self::EMAIL => 'Email',
            self::BACKUP_CODES => 'Backup Codes',
        };
    }

    /**
     * Check if this method requires enrollment
     */
    public function requiresEnrollment(): bool
    {
        return match($this) {
            self::TOTP => true,
            default => false,
        };
    }

    /**
     * Check if this method can be used as primary
     */
    public function canBePrimary(): bool
    {
        return match($this) {
            self::BACKUP_CODES => false,
            default => true,
        };
    }
}
