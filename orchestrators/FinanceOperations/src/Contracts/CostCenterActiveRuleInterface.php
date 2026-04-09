<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\RuleContexts\CostCenterActiveRuleContext;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Contract for validating target cost centers before allocation.
 */
interface CostCenterActiveRuleInterface
{
    public function check(CostCenterActiveRuleContext $context): RuleResult;

    public function getName(): string;
}
