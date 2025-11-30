<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

/**
 * Hierarchy Depth Exceeded Exception
 * 
 * Thrown when attempting to create a budget that exceeds maximum hierarchy depth.
 */
final class HierarchyDepthExceededException extends BudgetException
{
    public function __construct(
        private readonly int $currentDepth,
        private readonly int $maxDepth,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Budget hierarchy depth exceeded: Current depth %d, Maximum allowed %d',
            $currentDepth,
            $maxDepth
        );
        parent::__construct($message, $code);
    }

    public function getCurrentDepth(): int
    {
        return $this->currentDepth;
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }
}
