<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Tests\Unit\Services;

use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Exceptions\PolicyDecodeFailed;
use Nexus\PolicyEngine\Services\JsonPolicyDecoder;
use Nexus\PolicyEngine\Services\PolicyValidator;
use PHPUnit\Framework\TestCase;

final class JsonPolicyDecoderTest extends TestCase
{
    public function test_decode_builds_policy_definition_from_json(): void
    {
        $decoder = new JsonPolicyDecoder(new PolicyValidator());
        $json = <<<'JSON'
{
  "id": "policy.approval.risk",
  "version": "v1",
  "tenant_id": "tenant_a",
  "kind": "threshold",
  "strategy": "collect_all",
  "rules": [
    {
      "id": "rule.escalate.large",
      "priority": 200,
      "outcome": "escalate",
      "reason_code": "risk.large",
      "conditions": {
        "mode": "all",
        "items": [
          {"field": "amount", "operator": "gt", "value": 100000}
        ]
      },
      "obligations": [
        {"key": "notify", "value": "risk-team"}
      ]
    }
  ]
}
JSON;

        $definition = $decoder->decode($json);
        self::assertSame('policy.approval.risk', $definition->id->value);
        self::assertSame('v1', $definition->version->value);
        self::assertSame('tenant_a', $definition->tenantId->value);
        self::assertSame(PolicyKind::Threshold, $definition->kind);
        self::assertSame(EvaluationStrategy::CollectAll, $definition->strategy);
        self::assertCount(1, $definition->rules);
        self::assertSame('rule.escalate.large', $definition->rules[0]->id->value);
        self::assertSame(DecisionOutcome::Escalate, $definition->rules[0]->outcome);
    }

    public function test_decode_throws_on_invalid_json_shape(): void
    {
        $decoder = new JsonPolicyDecoder(new PolicyValidator());

        $this->expectException(PolicyDecodeFailed::class);
        $decoder->decode('{"id":"x","rules":"not-array"}');
    }

    public function test_decode_throws_on_malformed_json(): void
    {
        $decoder = new JsonPolicyDecoder(new PolicyValidator());

        $this->expectException(PolicyDecodeFailed::class);
        $decoder->decode('{"id":"x",');
    }

    public function test_decode_throws_when_required_top_level_field_missing(): void
    {
        $decoder = new JsonPolicyDecoder(new PolicyValidator());

        $this->expectException(PolicyDecodeFailed::class);
        $decoder->decode('{"id":"p","version":"v1","tenant_id":"t","strategy":"first_match","rules":[]}');
    }

    public function test_decode_throws_on_invalid_kind_enum(): void
    {
        $decoder = new JsonPolicyDecoder(new PolicyValidator());

        $this->expectException(PolicyDecodeFailed::class);
        $decoder->decode('{"id":"p","version":"v1","tenant_id":"t","kind":"unknown","strategy":"first_match","rules":[{"id":"r1","priority":1,"outcome":"approve","reason_code":"x","conditions":{"mode":"all","items":[{"field":"a","operator":"exists"}]}}]}');
    }

    public function test_decode_throws_when_rules_are_empty(): void
    {
        $decoder = new JsonPolicyDecoder(new PolicyValidator());

        $this->expectException(PolicyDecodeFailed::class);
        $decoder->decode('{"id":"p","version":"v1","tenant_id":"t","kind":"workflow","strategy":"first_match","rules":[]}');
    }
}
