<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Exceptions;

/**
 * Exception thrown when model is not found
 */
class ModelNotFoundException extends IntelligenceException
{
    public static function forName(string $tenantId, string $modelName): self
    {
        return new self(
            "Model '{$modelName}' not found for tenant '{$tenantId}'. " .
            "Please configure the model before use."
        );
    }
}
