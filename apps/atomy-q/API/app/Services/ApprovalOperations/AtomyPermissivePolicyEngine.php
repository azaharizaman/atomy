<?php

declare(strict_types=1);

namespace App\Services\ApprovalOperations;

use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Enums\DecisionOutcome;

/**
 * Alpha stub: always allows operational approval starts until policies are registered in L3.
 */
final readonly class AtomyPermissivePolicyEngine implements PolicyEngineInterface
{
    public function evaluate(PolicyRequest $request): PolicyDecision
    {
        return new PolicyDecision(
            outcome: DecisionOutcome::Allow,
            matchedRuleIds: [],
            reasonCodes: [],
            obligations: [],
            traceId: 'atomy-permissive-policy-stub',
        );
    }
}
