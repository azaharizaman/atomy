<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

use DomainException;

final class InvalidCurrentPasswordException extends DomainException
{
    public function __construct(string $message = 'Invalid current password', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
