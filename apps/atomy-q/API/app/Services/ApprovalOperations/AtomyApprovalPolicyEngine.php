<?php

declare(strict_types=1);

namespace App\Services\ApprovalOperations;

use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Contracts\PolicyRegistryInterface;
use Nexus\PolicyEngine\Contracts\PolicyValidatorInterface;
use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Exceptions\PolicyEngineException;
use Nexus\PolicyEngine\Exceptions\PolicyNotFound;
use Nexus\PolicyEngine\Services\PolicyEvaluator;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;

/**
 * App-level PolicyEngine binding.
 *
 * Operational approvals should fail closed: missing/invalid policies deny.
 */
final readonly class AtomyApprovalPolicyEngine implements PolicyEngineInterface
{
    private PolicyEvaluator $evaluator;

    public function __construct(
        PolicyRegistryInterface $registry,
        PolicyValidatorInterface $validator,
    ) {
        $this->evaluator = new PolicyEvaluator($registry, $validator);
    }

    public function evaluate(PolicyRequest $request): PolicyDecision
    {
        try {
            return $this->evaluator->evaluate($request);
        } catch (PolicyNotFound|PolicyEngineException) {
            return new PolicyDecision(
                outcome: DecisionOutcome::Deny,
                matchedRuleIds: [],
                reasonCodes: [new ReasonCode('policy.denied')],
                obligations: [],
                traceId: 'policy-deny-default',
            );
        }
    }
}
