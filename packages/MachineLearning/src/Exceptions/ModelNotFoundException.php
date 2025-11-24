<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when model is not found
 */
final class ModelNotFoundException extends MachineLearningException
{
    public static function forName(string $tenantId, string $modelName): self
    {
        return new self(
            "Model '{$modelName}' not found for tenant '{$tenantId}'. " .
            "Please configure the model before use."
        );
    }

    public static function forModel(string $modelName, ?string $version = null): self
    {
        $identifier = $version ? "{$modelName}@{$version}" : $modelName;
        
        return new self(
            sprintf(
                "Model '%s' not found. Verify model exists in registry or filesystem.",
                $identifier
            )
        );
    }
}
