<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Exceptions;

/**
 * Thrown when a user is not authorized to execute a query
 */
class UnauthorizedQueryException extends AnalyticsException
{
    public function __construct(string $userId, string $queryId, string $action = 'execute')
    {
        parent::__construct("User {$userId} is not authorized to {$action} query {$queryId}");
    }
}
