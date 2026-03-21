<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId;

final readonly class PolicyDefinition
{
    /**
     * @param list<RuleDefinition> $rules
     */
    public function __construct(
        public PolicyId $id,
        public PolicyVersion $version,
        public TenantId $tenantId,
        public PolicyKind $kind,
        public EvaluationStrategy $strategy,
        public array $rules,
    ) {
        if ($this->rules === []) {
            throw new \InvalidArgumentException('PolicyDefinition must contain at least one rule.');
        }
    }

    /**
     * @return list<RuleDefinition>
     */
    public function rulesByPriorityDesc(): array
    {
        $rules = $this->rules;
        usort(
            $rules,
            static fn (RuleDefinition $a, RuleDefinition $b): int => $b->priority <=> $a->priority
        );

        return $rules;
    }
}
