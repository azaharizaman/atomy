<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\ValueObjects\Obligation;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;

final readonly class PolicyDecision
{
    /**
     * @param list<string> $matchedRuleIds
     * @param list<ReasonCode> $reasonCodes
     * @param list<Obligation> $obligations
     */
    public function __construct(
        public DecisionOutcome $outcome,
        public array $matchedRuleIds,
        public array $reasonCodes,
        public array $obligations,
        public string $traceId,
    ) {
    }
}
