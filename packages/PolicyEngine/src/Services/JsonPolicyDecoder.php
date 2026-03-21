<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Services;

use Nexus\PolicyEngine\Contracts\PolicyDefinitionDecoderInterface;
use Nexus\PolicyEngine\Domain\Condition;
use Nexus\PolicyEngine\Domain\ConditionGroup;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Domain\RuleDefinition;
use Nexus\PolicyEngine\Enums\ConditionOperator;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\PolicyDecodeFailed;
use Nexus\PolicyEngine\ValueObjects\Obligation;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;
use Nexus\PolicyEngine\ValueObjects\RuleId;
use Nexus\PolicyEngine\ValueObjects\TenantId;

final readonly class JsonPolicyDecoder implements PolicyDefinitionDecoderInterface
{
    public function __construct(private PolicyValidator $validator)
    {
    }

    public function decode(string $payload): PolicyDefinition
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new PolicyDecodeFailed('Invalid JSON payload: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new PolicyDecodeFailed('Policy payload must decode to an object.');
        }
        if (!isset($decoded['id'], $decoded['version'], $decoded['tenant_id'], $decoded['kind'], $decoded['strategy'], $decoded['rules'])) {
            throw new PolicyDecodeFailed('Policy payload is missing required fields.');
        }
        if (!is_array($decoded['rules'])) {
            throw new PolicyDecodeFailed('Policy rules must be an array.');
        }

        $rules = [];
        foreach ($decoded['rules'] as $rule) {
            if (!is_array($rule)) {
                throw new PolicyDecodeFailed('Each rule must be an object.');
            }
            $rules[] = $this->decodeRule($rule);
        }

        try {
            $definition = new PolicyDefinition(
                new PolicyId((string) $decoded['id']),
                new PolicyVersion((string) $decoded['version']),
                new TenantId((string) $decoded['tenant_id']),
                PolicyKind::from((string) $decoded['kind']),
                EvaluationStrategy::from((string) $decoded['strategy']),
                $rules
            );
            $this->validator->validate($definition);
        } catch (\Throwable $e) {
            throw new PolicyDecodeFailed('Policy decode/validation failed: ' . $e->getMessage(), 0, $e);
        }

        return $definition;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function decodeRule(array $rule): RuleDefinition
    {
        if (!isset($rule['id'], $rule['priority'], $rule['outcome'], $rule['reason_code'], $rule['conditions'])) {
            throw new PolicyDecodeFailed('Rule payload is missing required fields.');
        }
        if (!is_array($rule['conditions'])) {
            throw new PolicyDecodeFailed('Rule conditions must be an object.');
        }
        $conditionsPayload = $rule['conditions'];
        if (!isset($conditionsPayload['mode'], $conditionsPayload['items']) || !is_array($conditionsPayload['items'])) {
            throw new PolicyDecodeFailed('Rule conditions must include mode and items.');
        }

        $conditions = [];
        foreach ($conditionsPayload['items'] as $item) {
            if (!is_array($item) || !isset($item['field'], $item['operator'])) {
                throw new PolicyDecodeFailed('Condition item must include field and operator.');
            }
            $conditions[] = new Condition(
                (string) $item['field'],
                ConditionOperator::from((string) $item['operator']),
                $item['value'] ?? null
            );
        }

        $obligations = [];
        $rawObligations = $rule['obligations'] ?? [];
        if (!is_array($rawObligations)) {
            throw new PolicyDecodeFailed('Rule obligations must be an array.');
        }
        foreach ($rawObligations as $obligation) {
            if (!is_array($obligation) || !isset($obligation['key'])) {
                throw new PolicyDecodeFailed('Each obligation must include key.');
            }
            $obligations[] = new Obligation((string) $obligation['key'], $obligation['value'] ?? null);
        }

        return new RuleDefinition(
            new RuleId((string) $rule['id']),
            (int) $rule['priority'],
            DecisionOutcome::from((string) $rule['outcome']),
            new ConditionGroup((string) $conditionsPayload['mode'], $conditions),
            new ReasonCode((string) $rule['reason_code']),
            $obligations
        );
    }
}
