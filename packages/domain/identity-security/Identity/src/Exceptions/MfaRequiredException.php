<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * MFA required exception
 * 
 * Thrown when MFA verification is required but not provided
 */
class MfaRequiredException extends \Exception
{
    public function __construct(string $message = 'Multi-factor authentication required', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
