<?php

declare(strict_types=1);

namespace App\Services\ApprovalOperations;

use Psr\Log\LoggerInterface;
use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
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
        PolicyEvaluator $evaluator,
        private LoggerInterface $logger,
    ) {
        $this->evaluator = $evaluator;
    }

    public function evaluate(PolicyRequest $request): PolicyDecision
    {
        try {
            return $this->evaluator->evaluate($request);
        } catch (PolicyNotFound|\Nexus\PolicyEngine\Exceptions\PolicyEngineException $exception) {
            $this->logger->warning('Operational approval policy evaluation denied; failing closed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'tenant_id' => $request->tenantId->value,
                'policy_id' => $request->policyId->value,
                'policy_version' => $request->policyVersion->value,
                'action' => $request->action,
            ]);

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
