<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

use DomainException;

final class TokenMissingTenantException extends DomainException
{
    public function __construct(string $message = 'Token is missing required tenant context', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
