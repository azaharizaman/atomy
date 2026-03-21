<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Services;

use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\PolicyValidationFailed;

final readonly class PolicyValidator
{
    public function validate(PolicyDefinition $definition): void
    {
        $priorities = [];
        foreach ($definition->rules as $rule) {
            if (isset($priorities[$rule->priority])) {
                throw new PolicyValidationFailed('Rule priorities must be unique within a policy version.');
            }
            $priorities[$rule->priority] = true;
            $this->assertOutcomeAllowedForKind($definition->kind, $rule->outcome);
        }
    }

    private function assertOutcomeAllowedForKind(PolicyKind $kind, DecisionOutcome $outcome): void
    {
        $allowed = match ($kind) {
            PolicyKind::Authorization => [DecisionOutcome::Allow, DecisionOutcome::Deny],
            PolicyKind::Workflow => [DecisionOutcome::Approve, DecisionOutcome::Reject, DecisionOutcome::Escalate, DecisionOutcome::Route],
            PolicyKind::Threshold => [DecisionOutcome::Approve, DecisionOutcome::Reject, DecisionOutcome::Escalate, DecisionOutcome::Route],
        };

        if (!in_array($outcome, $allowed, true)) {
            throw new PolicyValidationFailed(sprintf(
                'Outcome "%s" is not allowed for policy kind "%s".',
                $outcome->value,
                $kind->value
            ));
        }
    }
}
