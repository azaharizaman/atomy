<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Exceptions;

/**
 * Exception thrown for MFA enrollment errors.
 */
class MfaEnrollmentException extends IdentityOperationsException
{
    public static function enrollmentFailed(string $userId, ?string $reason = null): self
    {
        $message = $reason !== null
            ? "MFA enrollment failed for user '{$userId}': {$reason}"
            : "MFA enrollment failed for user '{$userId}'";
            
        return new self($message, ['user_id' => $userId, 'reason' => $reason]);
    }

    public static function verificationFailed(string $userId, int $remainingAttempts): self
    {
        return new self(
            "MFA verification failed for user '{$userId}'. {$remainingAttempts} attempts remaining",
            ['user_id' => $userId, 'remaining_attempts' => $remainingAttempts]
        );
    }

    public static function methodNotSupported(string $method): self
    {
        return new self("MFA method '{$method}' is not supported", ['method' => $method]);
    }
}
