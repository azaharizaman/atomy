<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid gap policy is provided.
 */
class InvalidGapPolicyException extends Exception
{
    public static function unknownPolicy(string $policy): self
    {
        return new self(
            "Invalid gap policy: '{$policy}'. Must be one of: allow_gaps, fill_gaps, report_gaps_only"
        );
    }
}
