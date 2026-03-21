<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Services;

use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Contracts\PolicyRegistryInterface;
use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Domain\RuleDefinition;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\TenantMismatch;
use Nexus\PolicyEngine\ValueObjects\Obligation;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;

final readonly class PolicyEvaluator implements PolicyEngineInterface
{
    public function __construct(
        private PolicyRegistryInterface $registry,
        private PolicyValidator $validator,
    ) {
    }

    public function evaluate(PolicyRequest $request): PolicyDecision
    {
        $definition = $this->registry->get($request->policyId, $request->policyVersion, $request->tenantId);
        if ($definition->tenantId->value !== $request->tenantId->value) {
            throw TenantMismatch::between($request->tenantId, $definition->tenantId);
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
        if ($definition->kind === PolicyKind::Authorization) {
            if ($matchedRules === []) {
                return DecisionOutcome::Deny;
            }
            $outcomes = array_map(static fn (RuleDefinition $r): DecisionOutcome => $r->outcome, $matchedRules);
            if (in_array(DecisionOutcome::Deny, $outcomes, true)) {
                return DecisionOutcome::Deny;
            }

            return DecisionOutcome::Allow;
        }

        // Workflow / threshold kind.
        if ($matchedRules === []) {
            return DecisionOutcome::Reject;
        }
        if ($definition->strategy === EvaluationStrategy::FirstMatch) {
            return $matchedRules[0]->outcome;
        }

        $priority = [
            DecisionOutcome::Escalate->value => 400,
            DecisionOutcome::Reject->value => 300,
            DecisionOutcome::Approve->value => 200,
            DecisionOutcome::Route->value => 100,
        ];
        $best = $matchedRules[0]->outcome;
        foreach ($matchedRules as $rule) {
            if (($priority[$rule->outcome->value] ?? 0) > ($priority[$best->value] ?? 0)) {
                $best = $rule->outcome;
            }
        }

        return $best;
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

        return sha1($fingerprint);
    }
}
