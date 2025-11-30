<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when inference execution fails
 */
final class InferenceException extends MachineLearningException
{
    public static function forModel(string $modelIdentifier, string $reason): self
    {
        return new self(
            sprintf(
                "Inference failed for model '%s': %s",
                $modelIdentifier,
                $reason
            )
        );
    }

    public static function invalidInput(string $reason): self
    {
        return new self("Invalid input for inference: {$reason}");
    }
}
