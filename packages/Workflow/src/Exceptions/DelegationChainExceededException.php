<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when delegation chain exceeds maximum depth.
 */
class DelegationChainExceededException extends \RuntimeException
{
    public static function maxDepth(int $maxDepth): self
    {
        return new self("Delegation chain cannot exceed {$maxDepth} levels.");
    }
}
