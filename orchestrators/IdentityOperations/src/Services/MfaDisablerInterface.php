<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for MFA disabling.
 */
interface MfaDisablerInterface
{
    public function disable(string $userId): void;
}
