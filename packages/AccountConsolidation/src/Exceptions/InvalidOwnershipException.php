<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Exceptions;

/**
 * Exception for invalid ownership percentages.
 */
final class InvalidOwnershipException extends ConsolidationException
{
    public function __construct(
        float $percentage,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("Invalid ownership percentage: {$percentage}%", $code, $previous);
    }
}
