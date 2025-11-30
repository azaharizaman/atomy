<?php

declare(strict_types=1);

namespace Nexus\Analytics\Exceptions;

/**
 * Thrown when a query definition is not found
 */
class QueryNotFoundException extends AnalyticsException
{
    public function __construct(string $queryId)
    {
        parent::__construct("Query not found: {$queryId}");
    }
}
