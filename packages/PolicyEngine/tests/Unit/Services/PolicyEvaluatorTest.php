<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Tests\Unit\Services;

use Nexus\PolicyEngine\Domain\Condition;
use Nexus\PolicyEngine\Domain\ConditionGroup;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Domain\RuleDefinition;
use Nexus\PolicyEngine\Enums\ConditionOperator;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\PolicyNotFound;
use Nexus\PolicyEngine\Services\InMemoryPolicyRegistry;
use Nexus\PolicyEngine\Services\PolicyEvaluator;
use Nexus\PolicyEngine\Services\PolicyValidator;
use Nexus\PolicyEngine\ValueObjects\Obligation;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;
use Nexus\PolicyEngine\ValueObjects\RuleId;
use Nexus\PolicyEngine\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class PolicyEvaluatorTest extends TestCase
{
    public function test_authorization_defaults_to_deny_when_no_rule_matches(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('auth_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Authorization,
                EvaluationStrategy::CollectAll,
                [
                    $this->rule('r_allow_admin', 100, DecisionOutcome::Allow, 'role', 'admin'),
                ]
            ),
        ]);

        $decision = $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_a'),
            new PolicyId('auth_policy'),
            new PolicyVersion('v1'),
            'approve',
            ['role' => 'viewer']
        ));

        self::assertSame(DecisionOutcome::Deny, $decision->outcome);
        self::assertSame([], $decision->matchedRuleIds);
    }

    public function test_authorization_deny_wins_in_collect_all_conflict(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('auth_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Authorization,
                EvaluationStrategy::CollectAll,
                [
                    $this->rule('r_allow_finance', 100, DecisionOutcome::Allow, 'department', 'finance'),
                    $this->rule('r_deny_suspended', 90, DecisionOutcome::Deny, 'is_suspended', true),
                ]
            ),
        ]);

        $decision = $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_a'),
            new PolicyId('auth_policy'),
            new PolicyVersion('v1'),
            'approve',
            ['department' => 'finance', 'is_suspended' => true]
        ));

        self::assertSame(DecisionOutcome::Deny, $decision->outcome);
        self::assertSame(['r_allow_finance', 'r_deny_suspended'], $decision->matchedRuleIds);
    }

    public function test_workflow_first_match_uses_highest_priority_match(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('wf_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Workflow,
                EvaluationStrategy::FirstMatch,
                [
                    $this->rule('r_escalate_large', 200, DecisionOutcome::Escalate, 'amount_band', 'large'),
                    $this->rule('r_approve_large', 100, DecisionOutcome::Approve, 'amount_band', 'large'),
                ]
            ),
        ]);

        $decision = $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_a'),
            new PolicyId('wf_policy'),
            new PolicyVersion('v1'),
            'submit',
            ['amount_band' => 'large']
        ));

        self::assertSame(DecisionOutcome::Escalate, $decision->outcome);
        self::assertSame(['r_escalate_large'], $decision->matchedRuleIds);
    }

    public function test_workflow_collect_all_applies_deterministic_precedence(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('wf_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Workflow,
                EvaluationStrategy::CollectAll,
                [
                    $this->rule('r_route_ops', 100, DecisionOutcome::Route, 'risk', 'medium'),
                    $this->rule('r_reject_blacklist', 90, DecisionOutcome::Reject, 'blacklisted', true),
                    $this->rule('r_escalate_special', 80, DecisionOutcome::Escalate, 'special_case', true),
                ]
            ),
        ]);

        $decision = $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_a'),
            new PolicyId('wf_policy'),
            new PolicyVersion('v1'),
            'submit',
            ['risk' => 'medium', 'blacklisted' => true, 'special_case' => true]
        ));

        self::assertSame(DecisionOutcome::Escalate, $decision->outcome);
        self::assertCount(3, $decision->matchedRuleIds);
    }

    public function test_threshold_policy_approves_when_amount_exceeds_limit(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('threshold_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Threshold,
                EvaluationStrategy::CollectAll,
                [
                    $this->rule('r_escalate_high', 200, DecisionOutcome::Escalate, 'amount', 100000, ConditionOperator::GreaterThan),
                    $this->rule('r_approve_ok', 100, DecisionOutcome::Approve, 'amount', 10000, ConditionOperator::GreaterThanOrEquals),
                ]
            ),
        ]);

        $decision = $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_a'),
            new PolicyId('threshold_policy'),
            new PolicyVersion('v1'),
            'submit',
            [],
            [],
            ['amount' => 50000]
        ));

        self::assertSame(DecisionOutcome::Approve, $decision->outcome);
        self::assertSame(['r_approve_ok'], $decision->matchedRuleIds);
    }

    public function test_threshold_policy_defaults_to_reject_when_no_rule_matches(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('threshold_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Threshold,
                EvaluationStrategy::FirstMatch,
                [
                    $this->rule('r_approve_ok', 100, DecisionOutcome::Approve, 'amount', 10000, ConditionOperator::GreaterThanOrEquals),
                ]
            ),
        ]);

        $decision = $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_a'),
            new PolicyId('threshold_policy'),
            new PolicyVersion('v1'),
            'submit',
            [],
            [],
            ['amount' => 100]
        ));

        self::assertSame(DecisionOutcome::Reject, $decision->outcome);
    }

    public function test_wrong_tenant_access_throws_not_found_semantics(): void
    {
        $engine = $this->makeEngineWithDefinitions([
            new PolicyDefinition(
                new PolicyId('auth_policy'),
                new PolicyVersion('v1'),
                new TenantId('tenant_a'),
                PolicyKind::Authorization,
                EvaluationStrategy::FirstMatch,
                [
                    $this->rule('r_allow_admin', 100, DecisionOutcome::Allow, 'role', 'admin'),
                ]
            ),
        ]);

        $this->expectException(PolicyNotFound::class);
        $engine->evaluate(new PolicyRequest(
            new TenantId('tenant_b'),
            new PolicyId('auth_policy'),
            new PolicyVersion('v1'),
            'approve',
            ['role' => 'admin']
        ));
    }

    private function makeEngineWithDefinitions(array $definitions): PolicyEvaluator
    {
        $registry = new InMemoryPolicyRegistry();
        foreach ($definitions as $definition) {
            $registry->put($definition);
        }

        return new PolicyEvaluator($registry, new PolicyValidator());
    }

    private function rule(
        string $id,
        int $priority,
        DecisionOutcome $outcome,
        string $field,
        mixed $value,
        ConditionOperator $operator = ConditionOperator::Equals,
    ): RuleDefinition {
        return new RuleDefinition(
            new RuleId($id),
            $priority,
            $outcome,
            new ConditionGroup('all', [new Condition($field, $operator, $value)]),
            new ReasonCode('reason.' . $id),
            [new Obligation('log', true)]
        );
    }
}
