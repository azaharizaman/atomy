<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Exceptions;

use RuntimeException;

/**
 * Thrown when external integration fails
 */
final class IntegrationFailedException extends RuntimeException
{
    public function __construct(
        string $service,
        string $operation,
        ?string $reason = null
    ) {
        $message = "Integration with {$service} failed during {$operation}";
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message);
    }
}
