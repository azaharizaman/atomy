<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

use DomainException;

/**
 * Mapping Exception
 *
 * Thrown when mapping between external models and domain entities fails.
 */
final class MappingException extends DomainException
{
    public static function invalidMfaMethod(string $method): self
    {
        return new self("Invalid MFA method: '{$method}'");
    }
}
