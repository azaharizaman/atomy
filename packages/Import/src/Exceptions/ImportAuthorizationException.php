<?php

declare(strict_types=1);

namespace Nexus\Import\Exceptions;

/**
 * Thrown when user lacks permission to perform import
 */
class ImportAuthorizationException extends ImportException
{
    public function __construct(
        string $action,
        string $resource,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = "Not authorized to {$action} on {$resource}";
        parent::__construct($message, $code, $previous);
    }
}
