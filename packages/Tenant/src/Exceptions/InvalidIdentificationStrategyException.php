<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Invalid Identification Strategy Exception
 *
 * Thrown when an invalid tenant identification strategy is provided.
 *
 * @package Nexus\Tenant\Exceptions
 */
class InvalidIdentificationStrategyException extends Exception
{
    public function __construct(string $message = 'Invalid identification strategy', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function unsupported(string $strategy): self
    {
        return new self("Unsupported identification strategy: '{$strategy}'. Valid strategies are: domain, subdomain, header, path, token");
    }
}
