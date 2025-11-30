<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid audit level is provided
 * Satisfies: BUS-AUD-0146
 *
 * @package Nexus\AuditLogger\Exceptions
 */
class InvalidAuditLevelException extends Exception
{
    public function __construct(int $level, int $code = 422, ?Exception $previous = null)
    {
        $message = "Invalid audit level: {$level}. Must be 1 (Low), 2 (Medium), 3 (High), or 4 (Critical)";
        parent::__construct($message, $code, $previous);
    }
}
