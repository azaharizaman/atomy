<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * SSO provider exception
 * 
 * Thrown when SSO provider encounters an error
 */
final class SsoProviderException extends SsoException
{
    public function __construct(string $providerName, string $reason)
    {
        parent::__construct("SSO provider '{$providerName}' error: {$reason}");
    }
}
