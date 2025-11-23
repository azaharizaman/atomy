<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Exceptions;

use Throwable;

/**
 * Exception thrown when CUSTOM evaluator fails.
 */
final class CustomEvaluatorException extends FeatureFlagException
{
    /**
     * Create exception for class not implementing CustomEvaluatorInterface.
     *
     * @param string $className The class name
     * @return self
     */
    public static function invalidInterface(string $className): self
    {
        return new self(
            "Custom evaluator class '{$className}' must implement CustomEvaluatorInterface"
        );
    }

    /**
     * Create exception for class instantiation failure.
     *
     * @param string $className The class name
     * @param Throwable $previous The previous exception
     * @return self
     */
    public static function instantiationFailed(string $className, Throwable $previous): self
    {
        return new self(
            "Failed to instantiate custom evaluator class '{$className}': {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for evaluation failure.
     *
     * @param string $className The class name
     * @param Throwable $previous The previous exception
     * @return self
     */
    public static function evaluationFailed(string $className, Throwable $previous): self
    {
        return new self(
            "Custom evaluator '{$className}' threw exception during evaluation: {$previous->getMessage()}",
            0,
            $previous
        );
    }
}
