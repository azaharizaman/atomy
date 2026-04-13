<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use DomainException;

/**
 * Exception thrown when a rule context is invalid.
 * 
 * @since 1.0.0
 */
final class InvalidRuleContextException extends DomainException
{
    public static function invalidParameter(string $parameter, string $reason): self
    {
        return new self(sprintf('Invalid rule context parameter "%s": %s', $parameter, $reason));
    }
}
