<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use Nexus\Treasury\Exceptions\TreasuryException;

/**
 * Exception thrown when a treasury policy is not found
 */
class TreasuryPolicyNotFoundException extends TreasuryException
{
    public function __construct(string $policyId)
    {
        parent::__construct("Treasury policy not found: {$policyId}");
    }
}
