<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

use RuntimeException;

/**
 * Sign Count Rollback Exception
 *
 * Thrown when authenticator sign count is lower than stored value,
 * indicating potential credential cloning attack.
 */
final class SignCountRollbackException extends RuntimeException
{
    public static function detected(int $storedCount, int $receivedCount): self
    {
        return new self(
            "Sign count rollback detected: stored={$storedCount}, received={$receivedCount}. " .
            "This may indicate credential cloning."
        );
    }
}
