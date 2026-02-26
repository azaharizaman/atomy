<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Exceptions;

/**
 * Thrown when query execution fails
 */
class QueryExecutionException extends AnalyticsException
{
    public function __construct(string $queryId, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Query execution failed for {$queryId}: {$reason}", 0, $previous);
    }
}
