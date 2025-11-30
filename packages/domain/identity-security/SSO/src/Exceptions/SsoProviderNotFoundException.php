<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * SSO provider not found exception
 * 
 * Thrown when a requested SSO provider is not registered
 */
final class SsoProviderNotFoundException extends SsoException
{
    public function __construct(string $providerName)
    {
        parent::__construct("SSO provider '{$providerName}' not found");
    }
}
