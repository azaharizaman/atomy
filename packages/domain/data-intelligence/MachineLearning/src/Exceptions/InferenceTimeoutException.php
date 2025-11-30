<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when an inference operation exceeds the configured timeout
 * 
 * This exception is raised when:
 * - External API provider takes longer than configured timeout (e.g., ml.openai.timeout)
 * - Local inference engine exceeds processing time limit
 * - Model loading takes too long
 * 
 * The exception includes the operation type and timeout duration for debugging.
 * 
 * Example:
 * ```php
 * throw InferenceTimeoutException::forOperation('predict', 30.0);
 * ```
 */
final class InferenceTimeoutException extends MachineLearningException
{
    private string $operation;
    private float $timeoutSeconds;

    public function __construct(string $message, string $operation, float $timeoutSeconds)
    {
        parent::__construct($message);
        $this->operation = $operation;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public static function forOperation(string $operation, float $timeoutSeconds): self
    {
        return new self(
            sprintf(
                "ML operation '%s' exceeded timeout of %.2f seconds",
                $operation,
                $timeoutSeconds
            ),
            $operation,
            $timeoutSeconds
        );
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getTimeoutSeconds(): float
    {
        return $this->timeoutSeconds;
    }
}
