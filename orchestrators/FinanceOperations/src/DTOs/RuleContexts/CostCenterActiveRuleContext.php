<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\RuleContexts;

/**
 * Typed context for cost center activation checks.
 */
final readonly class CostCenterActiveRuleContext
{
    /**
     * @param array<string> $costCenterIds
     */
    public function __construct(
        public string $tenantId,
        public array $costCenterIds,
    ) {}
}
