<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Exceptions;

use Exception;

/**
 * Exception thrown when an audit log is not found
 *
 * @package Nexus\AuditLogger\Exceptions
 */
class AuditLogNotFoundException extends Exception
{
    public function __construct(string $identifier, int $code = 404, ?Exception $previous = null)
    {
        $message = "Audit log not found: {$identifier}";
        parent::__construct($message, $code, $previous);
    }
}
