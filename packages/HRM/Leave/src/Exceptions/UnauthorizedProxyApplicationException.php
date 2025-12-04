<?php

declare(strict_types=1);

namespace Nexus\Leave\Exceptions;

/**
 * Exception thrown when a user attempts to apply leave on behalf of another
 * employee without proper authorization.
 */
final class UnauthorizedProxyApplicationException extends LeaveException
{
    public function __construct(string $applicantId, string $employeeId)
    {
        parent::__construct(
            sprintf(
                "User '%s' is not authorized to apply leave on behalf of employee '%s'",
                $applicantId,
                $employeeId
            )
        );
    }
}
