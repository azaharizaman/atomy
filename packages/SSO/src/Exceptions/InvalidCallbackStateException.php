<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * Invalid callback state exception
 * 
 * Thrown when state validation fails (CSRF protection)
 */
final class InvalidCallbackStateException extends SsoException
{
}
