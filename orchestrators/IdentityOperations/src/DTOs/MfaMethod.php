<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * MFA method enumeration.
 */
enum MfaMethod: string
{
    case TOTP = 'totp';
    case SMS = 'sms';
    case EMAIL = 'email';
    case BACKUP_CODES = 'backup_codes';
}
