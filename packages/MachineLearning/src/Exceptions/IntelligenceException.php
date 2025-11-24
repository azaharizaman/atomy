<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

use RuntimeException;

/**
 * Base intelligence exception
 */
class IntelligenceException extends RuntimeException
{
    public static function forGenericError(string $message): self
    {
        return new self($message);
    }
}
