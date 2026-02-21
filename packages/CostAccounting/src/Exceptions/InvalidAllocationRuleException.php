<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Exceptions;

/**
 * Invalid Allocation Rule Exception
 * 
 * Thrown when a cost allocation rule is invalid or
 * fails validation.
 */
class InvalidAllocationRuleException extends CostAccountingException
{
    public function __construct(
        private string $ruleId,
        private string $reason
    ) {
        parent::__construct(
            sprintf(
                'Invalid allocation rule %s: %s',
                $ruleId,
                $reason
            )
        );
    }

    public function getRuleId(): string
    {
        return $this->ruleId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
