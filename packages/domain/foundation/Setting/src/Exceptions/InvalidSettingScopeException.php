<?php

declare(strict_types=1);

namespace Nexus\Setting\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid setting scope is provided.
 */
class InvalidSettingScopeException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $scope The invalid scope value
     */
    public function __construct(string $scope)
    {
        parent::__construct("Invalid setting scope: {$scope}. Must be 'user', 'tenant', or 'application'.");
    }

    /**
     * Get the invalid scope value.
     */
    public function getScope(): string
    {
        preg_match('/scope: (.+?)\./', $this->getMessage(), $matches);

        return $matches[1] ?? '';
    }
}
