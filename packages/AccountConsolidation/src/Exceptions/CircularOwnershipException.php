<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Exceptions;

/**
 * Exception for circular ownership structures.
 */
final class CircularOwnershipException extends ConsolidationException
{
    public function __construct(
        string $entityId,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("Circular ownership detected for entity: {$entityId}", $code, $previous);
    }
}
