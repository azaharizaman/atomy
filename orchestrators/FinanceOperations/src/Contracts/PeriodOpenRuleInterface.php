<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\RuleContexts\PeriodOpenRuleContext;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Interface for period-open rule checks.
 */
interface PeriodOpenRuleInterface
{
    public function check(PeriodOpenRuleContext $context): RuleResult;

    public function getName(): string;
}
