<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Exceptions;

use Exception;

/**
 * Exception thrown when required audit log fields are missing
 * Satisfies: BUS-AUD-0145
 *
 * @package Nexus\AuditLogger\Exceptions
 */
class MissingRequiredFieldException extends Exception
{
    public function __construct(string $fieldName, int $code = 422, ?Exception $previous = null)
    {
        $message = "Missing required audit log field: {$fieldName}";
        parent::__construct($message, $code, $previous);
    }
}
