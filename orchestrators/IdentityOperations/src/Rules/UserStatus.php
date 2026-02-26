<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

/**
 * User status enumeration for rules.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';
}
