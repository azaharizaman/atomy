<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\RuleContexts;

/**
 * Typed context for period-open rule evaluation.
 */
final readonly class PeriodOpenRuleContext
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
    ) {}
}
