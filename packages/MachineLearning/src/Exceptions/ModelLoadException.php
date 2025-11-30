<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when model loading fails
 */
final class ModelLoadException extends MachineLearningException
{
    public static function forModel(string $modelName, string $reason): self
    {
        return new self(
            sprintf(
                "Failed to load model '%s': %s",
                $modelName,
                $reason
            )
        );
    }
}
