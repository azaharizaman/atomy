<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\RuleContexts\GLAccountMappingRuleContext;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Contract for GL account mapping validation rule.
 */
interface GLAccountMappingRuleInterface
{
    public function check(GLAccountMappingRuleContext $context): RuleResult;

    public function getName(): string;
}
