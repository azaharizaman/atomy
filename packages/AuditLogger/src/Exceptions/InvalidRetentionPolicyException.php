<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid retention policy is provided
 * Satisfies: BUS-AUD-0147
 *
 * @package Nexus\AuditLogger\Exceptions
 */
class InvalidRetentionPolicyException extends Exception
{
    public function __construct(int $days, int $code = 422, ?Exception $previous = null)
    {
        $message = "Invalid retention policy: {$days} days. Retention days cannot be negative.";
        parent::__construct($message, $code, $previous);
    }
}
