<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when an inference engine is requested but not available in the runtime environment
 * 
 * This exception is raised when:
 * - PyTorchInferenceEngine is used but Python/torch is not installed
 * - OnnxInferenceEngine is used but ONNX runtime is not available
 * - Required PHP extensions (ext-ffi) are missing
 * 
 * The exception provides clear guidance on what needs to be installed.
 * 
 * Example:
 * ```php
 * throw InferenceEngineUnavailableException::missingDependency(
 *     'PyTorchInferenceEngine',
 *     'Python 3.8+ with torch package',
 *     'pip install torch'
 * );
 * ```
 */
final class InferenceEngineUnavailableException extends MachineLearningException
{
    private string $engineName;
    private string $missingDependency;

    public function __construct(string $message, string $engineName, string $missingDependency)
    {
        parent::__construct($message);
        $this->engineName = $engineName;
        $this->missingDependency = $missingDependency;
    }

    public static function missingDependency(
        string $engineName,
        string $missingDependency,
        string $installCommand
    ): self {
        return new self(
            sprintf(
                "Inference engine '%s' requires %s which is not available. Install with: %s",
                $engineName,
                $missingDependency,
                $installCommand
            ),
            $engineName,
            $missingDependency
        );
    }

    public function getEngineName(): string
    {
        return $this->engineName;
    }

    public function getMissingDependency(): string
    {
        return $this->missingDependency;
    }
}
