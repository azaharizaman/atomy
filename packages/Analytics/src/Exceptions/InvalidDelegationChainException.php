<?php

declare(strict_types=1);

namespace Nexus\Analytics\Exceptions;

/**
 * Thrown when delegation chain validation fails
 */
class InvalidDelegationChainException extends AnalyticsException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid delegation chain: {$reason}");
    }
}
