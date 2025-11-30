<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * SSO session expired exception
 * 
 * Thrown when attempting to use an expired SSO session
 */
final class SsoSessionExpiredException extends SsoException
{
    public function __construct(string $sessionId)
    {
        parent::__construct("SSO session '{$sessionId}' has expired");
    }
}
