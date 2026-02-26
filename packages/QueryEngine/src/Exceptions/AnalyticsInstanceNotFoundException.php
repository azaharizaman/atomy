<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Exceptions;

/**
 * Thrown when analytics instance is not found
 */
class AnalyticsInstanceNotFoundException extends AnalyticsException
{
    public function __construct(string $modelType, string $modelId)
    {
        parent::__construct("Analytics instance not found for {$modelType}:{$modelId}");
    }
}
