<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

/**
 * SSO authentication exception
 * 
 * Thrown when SSO authentication fails
 */
class SsoAuthenticationException extends \Exception
{
    public function __construct(string $provider, string $reason, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "SSO authentication failed ({$provider}): {$reason}";
        parent::__construct($message, $code, $previous);
    }
}
