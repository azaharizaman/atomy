<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Tests\Unit\Services;

use Nexus\PolicyEngine\Domain\Condition;
use Nexus\PolicyEngine\Domain\ConditionGroup;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Domain\RuleDefinition;
use Nexus\PolicyEngine\Enums\ConditionOperator;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\PolicyValidationFailed;
use Nexus\PolicyEngine\Services\PolicyValidator;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;
use Nexus\PolicyEngine\ValueObjects\RuleId;
use Nexus\PolicyEngine\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class PolicyValidatorTest extends TestCase
{
    public function test_validate_throws_on_duplicate_priorities(): void
    {
        $validator = new PolicyValidator();

        $definition = new PolicyDefinition(
            new PolicyId('auth_policy'),
            new PolicyVersion('v1'),
            new TenantId('tenant_a'),
            PolicyKind::Authorization,
            EvaluationStrategy::CollectAll,
            [
                $this->rule('r1', 100, DecisionOutcome::Allow),
                $this->rule('r2', 100, DecisionOutcome::Deny),
            ]
        );

        $this->expectException(PolicyValidationFailed::class);
        $validator->validate($definition);
    }

    public function test_validate_throws_on_invalid_outcome_for_kind(): void
    {
        $validator = new PolicyValidator();

        $definition = new PolicyDefinition(
            new PolicyId('wf_policy'),
            new PolicyVersion('v1'),
            new TenantId('tenant_a'),
            PolicyKind::Workflow,
            EvaluationStrategy::FirstMatch,
            [
                $this->rule('r1', 100, DecisionOutcome::Allow),
            ]
        );

        $this->expectException(PolicyValidationFailed::class);
        $validator->validate($definition);
    }

    private function rule(string $id, int $priority, DecisionOutcome $outcome): RuleDefinition
    {
        return new RuleDefinition(
            new RuleId($id),
            $priority,
            $outcome,
            new ConditionGroup('all', [new Condition('action', ConditionOperator::Equals, 'view')]),
            new ReasonCode('reason.' . $id)
        );
    }
}
