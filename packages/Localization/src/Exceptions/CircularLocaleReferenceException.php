<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a circular reference is detected in locale parent chain.
 *
 * Example: ms_MY → ms → ms_MY (circular)
 */
final class CircularLocaleReferenceException extends LocalizationException
{
    /**
     * @param array<int, string> $visitedCodes
     */
    public function __construct(array $visitedCodes)
    {
        $chain = implode(' → ', $visitedCodes);
        parent::__construct("Circular reference detected in locale chain: {$chain}");
    }
}
