<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

/**
 * Base exception for all KYC verification related errors.
 */
class KycVerificationException extends \Exception
{
    /**
     * Create exception with party context
     */
    public static function forParty(string $partyId, string $message, ?\Throwable $previous = null): self
    {
        return new self(
            message: sprintf('[Party: %s] %s', $partyId, $message),
            previous: $previous
        );
    }
}
