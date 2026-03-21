# Nexus\PolicyEngine

## Overview

`Nexus\PolicyEngine` is a Layer 1 package that evaluates tenant-scoped policies with deterministic outcomes.
Version 1 supports:

- Authorization decisions
- Workflow decisions
- Threshold/limit runtime decisions (numeric comparator-based)

## Architecture

- Layer 1 (pure PHP, framework-agnostic)
- Interface-first boundaries via `src/Contracts`
- Immutable domain/value objects
- Deterministic conflict resolution and default outcomes

## Key Interfaces

- `Nexus\PolicyEngine\Contracts\PolicyEngineInterface`
- `Nexus\PolicyEngine\Contracts\PolicyRegistryInterface`
- `Nexus\PolicyEngine\Contracts\PolicyCompilerInterface` (reserved for future compilation/caching use in v1)
- `Nexus\PolicyEngine\Contracts\PolicyDefinitionDecoderInterface` (JSON to typed domain policy)

## Requirements Mapping

- Authorization policy evaluation (A)
- Workflow policy evaluation (B)
- Threshold policy runtime (C: `GT`, `GTE`, `LT`, `LTE`, `BETWEEN`)
- Stateless JSON policy decoding and domain validation

## Multi-Layer Usage Pattern

- **Layer 1 (`Nexus\PolicyEngine`)**
  - Owns typed policy model, evaluator, validator, and JSON decode contract/service.
  - Remains stateless and framework-agnostic.
- **Layer 2 (orchestrators)**
  - Coordinates when policy decode/evaluate is invoked in business workflows.
  - Composes cross-package context before calling the engine.
- **Layer 3 (adapters/apps)**
  - Provides storage-backed policy registries and transport concerns (HTTP/UI/DB).
  - Admin UI and persistence adapters live here, not in Layer 1.

## Installation

From monorepo root, ensure autoload is refreshed:

`composer dump-autoload`

Run package tests:

`vendor/bin/phpunit -c packages/PolicyEngine/phpunit.xml`

## Usage Examples

### 1) Authorization policy evaluation

Use this when deciding if a subject can perform an action on a resource.

```php
<?php

declare(strict_types=1);

use Nexus\PolicyEngine\Domain\Condition;
use Nexus\PolicyEngine\Domain\ConditionGroup;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Domain\RuleDefinition;
use Nexus\PolicyEngine\Enums\ConditionOperator;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\Enums\EvaluationStrategy;
use Nexus\PolicyEngine\Enums\PolicyKind;
use Nexus\PolicyEngine\Services\InMemoryPolicyRegistry;
use Nexus\PolicyEngine\Services\PolicyEvaluator;
use Nexus\PolicyEngine\Services\PolicyValidator;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\ReasonCode;
use Nexus\PolicyEngine\ValueObjects\RuleId;
use Nexus\PolicyEngine\ValueObjects\TenantId;

$registry = new InMemoryPolicyRegistry();
$validator = new PolicyValidator();
$engine = new PolicyEvaluator($registry, $validator);

$policy = new PolicyDefinition(
    new PolicyId('auth.rfq.approve'),
    new PolicyVersion('v1'),
    new TenantId('tenant_a'),
    PolicyKind::Authorization,
    EvaluationStrategy::CollectAll,
    [
        new RuleDefinition(
            new RuleId('allow.buyer'),
            100,
            DecisionOutcome::Allow,
            new ConditionGroup('all', [
                new Condition('role', ConditionOperator::Equals, 'buyer'),
            ]),
            new ReasonCode('role.allowed')
        ),
        new RuleDefinition(
            new RuleId('deny.suspended'),
            90,
            DecisionOutcome::Deny,
            new ConditionGroup('all', [
                new Condition('is_suspended', ConditionOperator::Equals, true),
            ]),
            new ReasonCode('account.suspended')
        ),
    ]
);

$registry->put($policy);

$decision = $engine->evaluate(new PolicyRequest(
    new TenantId('tenant_a'),
    new PolicyId('auth.rfq.approve'),
    new PolicyVersion('v1'),
    'approve',
    ['role' => 'buyer', 'is_suspended' => false]
));

// $decision->outcome === DecisionOutcome::Allow
```

### 2) Workflow routing/escalation

Use workflow policies to drive approve/reject/escalate/route decisions.

```php
$decision = $engine->evaluate(new PolicyRequest(
    new TenantId('tenant_a'),
    new PolicyId('workflow.rfq.review'),
    new PolicyVersion('v1'),
    'submit',
    [],
    [],
    ['risk_level' => 'high', 'amount' => 250000]
));

// Example expected outcome:
// DecisionOutcome::Escalate
```

### 3) Threshold runtime evaluation

Threshold policies support numeric comparators:
- `gt`, `gte`, `lt`, `lte`, `between`

```php
$thresholdRule = new RuleDefinition(
    new RuleId('escalate.high.value'),
    200,
    DecisionOutcome::Escalate,
    new ConditionGroup('all', [
        new Condition('amount', ConditionOperator::GreaterThan, 100000),
    ]),
    new ReasonCode('threshold.exceeded')
);
```

### 4) Decode policy JSON into typed domain model

This is useful when policies are authored/stored outside PHP and loaded by adapters.

```php
use Nexus\PolicyEngine\Services\JsonPolicyDecoder;

$decoder = new JsonPolicyDecoder(new PolicyValidator());
$definition = $decoder->decode($jsonPayload);

// $definition is a validated PolicyDefinition ready for registry/evaluation.
```

## License

MIT
