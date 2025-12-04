<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Contract for payroll validation rules
 */
interface PayrollRuleInterface
{
    /**
     * Check if payroll calculation passes this rule
     */
    public function check(PayrollContext $context): RuleCheckResult;

    /**
     * Get human-readable rule name
     */
    public function getName(): string;
}
