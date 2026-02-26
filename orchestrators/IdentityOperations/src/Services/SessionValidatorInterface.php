<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for session validation.
 */
interface SessionValidatorInterface
{
    public function isValid(string $sessionId): bool;
}
