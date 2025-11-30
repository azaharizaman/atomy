<?php

declare(strict_types=1);

namespace Nexus\Analytics\Exceptions;

/**
 * Thrown when a data source operation fails
 */
class DataSourceException extends AnalyticsException
{
    public function __construct(string $sourceName, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Data source '{$sourceName}' failed: {$reason}", 0, $previous);
    }
}
