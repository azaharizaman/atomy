<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\RuleContexts\SubledgerClosedRuleContext;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Contract for subledger closure rule checks.
 */
interface SubledgerClosedRuleInterface
{
    public function check(SubledgerClosedRuleContext $context): RuleResult;

    public function getName(): string;
}
