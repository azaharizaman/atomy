<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when an approval strategy name is not registered on the engine.
 */
class UnknownApprovalStrategyException extends \RuntimeException
{
    public static function forStrategy(string $strategyName): self
    {
        return new self("Approval strategy '{$strategyName}' not found.");
    }
}
