<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Exceptions;

/**
 * Exception thrown when feature schema version doesn't match model expectation
 */
class FeatureVersionMismatchException extends IntelligenceException
{
    public static function forMismatch(string $modelName, string $expected, string $actual): self
    {
        return new self(
            "Feature version mismatch for model '{$modelName}': " .
            "expected version '{$expected}', but got '{$actual}'. " .
            "Please update the feature extractor or model configuration."
        );
    }
}
