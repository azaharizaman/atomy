<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Services;

use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Contracts\PolicyRegistryInterface;
use Nexus\PolicyEngine\Contracts\PolicyValidatorInterface;
use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Domain\RuleDefinition;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\PolicyEvaluationFailed;
use Nexus\PolicyEngine\Exceptions\PolicyNotFound;
use Nexus\PolicyEngine\Exceptions\UnsupportedPolicyKind;
use Nexus\PolicyEngine\ValueObjects\Obligation;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;

final readonly class PolicyEvaluator implements PolicyEngineInterface
{
    /** @var array<string, int> */
    private const OUTCOME_PRECEDENCE = [
        DecisionOutcome::Escalate->value => 400,
        DecisionOutcome::Reject->value => 300,
        DecisionOutcome::Approve->value => 200,
        DecisionOutcome::Route->value => 100,
    ];

    public function __construct(
        private PolicyRegistryInterface $registry,
        private PolicyValidatorInterface $validator,
    ) {
    }

    public function evaluate(PolicyRequest $request): PolicyDecision
    {
        $definition = $this->registry->get($request->policyId, $request->policyVersion, $request->tenantId);
        if ($definition->tenantId->value !== $request->tenantId->value) {
            throw PolicyNotFound::for($request->policyId, $request->policyVersion);
        }

        $this->validator->validate($definition);

        $context = $request->evaluationContext();
        $matched = [];
        foreach ($definition->rulesByPriorityDesc() as $rule) {
            if ($rule->matches($context)) {
                $matched[] = $rule;
                if ($definition->strategy === EvaluationStrategy::FirstMatch) {
                    break;
                }
            }
        }

        return $this->buildDecision($definition, $matched);
    }

    /**
     * @param list<RuleDefinition> $matchedRules
     */
    private function buildDecision(PolicyDefinition $definition, array $matchedRules): PolicyDecision
    {
        $outcome = $this->resolveOutcome($definition, $matchedRules);
        $matchedRuleIds = array_map(static fn (RuleDefinition $r): string => $r->id->value, $matchedRules);
        $reasonCodes = array_map(static fn (RuleDefinition $r): ReasonCode => $r->reasonCode, $matchedRules);
        $obligations = [];
        foreach ($matchedRules as $rule) {
            foreach ($rule->obligations as $obligation) {
                $obligations[] = $obligation;
            }
        }

        return new PolicyDecision(
            $outcome,
            $matchedRuleIds,
            $reasonCodes,
            $obligations,
            $this->buildTraceId($definition, $outcome, $matchedRuleIds, $reasonCodes, $obligations)
        );
    }

    /**
     * @param list<RuleDefinition> $matchedRules
     */
    private function resolveOutcome(PolicyDefinition $definition, array $matchedRules): DecisionOutcome
    {
        return match ($definition->kind) {
            PolicyKind::Authorization => (function () use ($matchedRules): DecisionOutcome {
                if ($matchedRules === []) {
                    return DecisionOutcome::Deny;
                }
                $outcomes = array_map(static fn (RuleDefinition $r): DecisionOutcome => $r->outcome, $matchedRules);
                if (in_array(DecisionOutcome::Deny, $outcomes, true)) {
                    return DecisionOutcome::Deny;
                }

                return DecisionOutcome::Allow;
            })(),
            PolicyKind::Workflow, PolicyKind::Threshold => (function () use ($matchedRules, $definition): DecisionOutcome {
                if ($matchedRules === []) {
                    return DecisionOutcome::Reject;
                }
                if ($definition->strategy === EvaluationStrategy::FirstMatch) {
                    return $matchedRules[0]->outcome;
                }

                $best = $matchedRules[0]->outcome;
                foreach ($matchedRules as $rule) {
                    if ((self::OUTCOME_PRECEDENCE[$rule->outcome->value] ?? 0) > (self::OUTCOME_PRECEDENCE[$best->value] ?? 0)) {
                        $best = $rule->outcome;
                    }
                }

                return $best;
            })(),
            default => throw UnsupportedPolicyKind::for($definition->kind),
        };
    }

    /**
     * @param list<string> $matchedRuleIds
     * @param list<ReasonCode> $reasonCodes
     * @param list<Obligation> $obligations
     */
    private function buildTraceId(
        PolicyDefinition $definition,
        DecisionOutcome $outcome,
        array $matchedRuleIds,
        array $reasonCodes,
        array $obligations,
    ): string {
        try {
            $fingerprint = json_encode([
                'tenant' => $definition->tenantId->value,
                'policy' => $definition->id->value,
                'version' => $definition->version->value,
                'kind' => $definition->kind->value,
                'strategy' => $definition->strategy->value,
                'outcome' => $outcome->value,
                'rules' => $matchedRuleIds,
                'reasons' => array_map(static fn (ReasonCode $r): string => $r->value, $reasonCodes),
                'obligations' => array_map(
                    static fn (Obligation $o): array => ['key' => $o->key, 'value' => $o->value],
                    $obligations
                ),
            ], JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new PolicyEvaluationFailed('Failed to serialize policy fingerprint', 0, $e);
        }

        return sha1($fingerprint);
    }
}
