<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\RuleContexts;

use Nexus\FinanceOperations\Enums\SubledgerType;

/**
 * Typed context for GL account mapping rule evaluation.
 */
final readonly class GLAccountMappingRuleContext
{
    /**
     * @param list<string> $transactionTypes
     */
    public function __construct(
        public string $tenantId,
        public SubledgerType $subledgerType,
        public array $transactionTypes,
    ) {}
}
