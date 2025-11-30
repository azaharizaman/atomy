<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * @deprecated Use MachineLearningException instead. This class is kept for backward compatibility only.
 * Will be removed in version 3.0.0
 */
class IntelligenceException extends MachineLearningException
{
    public static function forGenericError(string $message): self
    {
        return new self($message);
    }
}
