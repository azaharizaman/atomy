<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\ValueObjects\Obligation;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;
use Nexus\PolicyEngine\ValueObjects\RuleId;

final readonly class RuleDefinition
{
    /**
     * @param list<Obligation> $obligations
     */
    public function __construct(
        public RuleId $id,
        public int $priority,
        public DecisionOutcome $outcome,
        public ConditionGroup $conditions,
        public ReasonCode $reasonCode,
        public array $obligations = [],
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function matches(array $context): bool
    {
        return $this->conditions->matches($context);
    }
}
