<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\RuleContexts;

use Nexus\FinanceOperations\Enums\SubledgerType;

/**
 * Typed context for subledger closure rule evaluation.
 */
final readonly class SubledgerClosedRuleContext
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public SubledgerType $subledgerType,
    ) {}
}
