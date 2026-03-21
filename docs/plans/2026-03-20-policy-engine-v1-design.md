# Nexus PolicyEngine v1 Design (A+B+C Runtime)

Date: 2026-03-20  
Status: Fully implemented  
Owner: Coding Agent + Architecture Review

## 1) Objective

Introduce `Nexus\PolicyEngine` as a Layer 1 package that provides deterministic policy evaluation for:

- Authorization decisions (A)
- Workflow decisions (B)
- Threshold/limit decisions (C)

## 2) Scope Control (Critical)

### In Scope (Implement Now)

1. Typed, in-code policy model (no JSON interpreter in v1)
2. Policy evaluation contract + deterministic evaluator
3. Policy registry contract (tenant-scoped)
4. Policy request/decision immutable VOs
5. Rule priority, strategy, and conflict resolution
6. Domain exceptions and validation
7. Unit tests covering happy paths + conflict + tenant safety + invalid configs
8. Package-level implementation summary updates

### On Hold (Documented, Do Not Implement)

1. Persistence adapters and DB schema
2. UI/admin authoring tools
3. Event outbox integration
4. Caching/distributed compilation

### Future Reserved Hooks (for v2+)

- Advanced threshold semantics (composite formulas, weighted scorecards)
- Adapter-facing parser boundary for external policy representations

## 3) Architecture Constraints

- Layer 1 only: pure PHP, no framework imports.
- Strict typing in every file.
- Immutable VOs and readonly class usage where applicable.
- Tenant must be mandatory in request and registry resolution.
- Deterministic behavior: same input => same output.
- No side effects in evaluation path.

## 4) Proposed Package Skeleton

Target: `packages/PolicyEngine/`

```text
packages/PolicyEngine/
  src/
    Contracts/
      PolicyEngineInterface.php
      PolicyRegistryInterface.php
      PolicyCompilerInterface.php          # optional in v1, included for future-proofing
    Domain/
      PolicyDefinition.php
      RuleDefinition.php
      ConditionGroup.php
      PolicyRequest.php
      PolicyDecision.php
      Enums/
        PolicyKind.php                     # AUTHORIZATION, WORKFLOW (+ THRESHOLD reserved)
        EvaluationStrategy.php             # FIRST_MATCH, COLLECT_ALL
        DecisionOutcome.php                # ALLOW, DENY, APPROVE, REJECT, ESCALATE, ROUTE
      ValueObjects/
        PolicyId.php
        PolicyVersion.php
        RuleId.php
        TenantId.php
        ReasonCode.php
        Obligation.php
      Exceptions/
        PolicyNotFound.php
        PolicyValidationFailed.php
        PolicyEvaluationFailed.php
        UnsupportedPolicyKind.php
        TenantMismatch.php
    Services/
      PolicyEvaluator.php                  # default deterministic evaluator
      PolicyValidator.php
  tests/
    Unit/
      Services/
        PolicyEvaluatorTest.php
        PolicyValidatorTest.php
      Domain/
        PolicyDefinitionTest.php
        PolicyRequestTest.php
  IMPLEMENTATION_SUMMARY.md
  README.md
```

## 5) Contract and Behavior Baseline

### `PolicyEngineInterface`

- `evaluate(PolicyRequest $request): PolicyDecision`

### `PolicyRegistryInterface`

- `get(PolicyId $id, PolicyVersion $version, TenantId $tenantId): PolicyDefinition`

### Evaluation rules

- Authorization:
  - Default deny unless an allow rule matches.
  - Deny wins on higher or equal priority conflict.
- Workflow:
  - Highest-priority matching rule determines outcome.
  - Ties resolved by deterministic precedence table.
- Strategy:
  - `FIRST_MATCH` or `COLLECT_ALL` is policy-defined.

## 6) Delivery Phases with Tracking

### Phase 0 - Package Scaffolding

Deliverables:
- Package directories and namespace setup
- Contracts + enums + base VOs
- Initial `IMPLEMENTATION_SUMMARY.md`

Done criteria:
- Package autoloads
- No framework imports
- Baseline unit tests running

### Phase 1 - Core Evaluator (A+B+C runtime)

Deliverables:
- `PolicyValidator` and `PolicyEvaluator`
- Conflict resolution logic
- Domain exceptions

Done criteria:
- Deterministic evaluator tests passing
- Validation failures produce domain exceptions

### Phase 2 - Harden and Document

Deliverables:
- Expanded edge-case tests
- README usage examples
- Explicit "On Hold" section refreshed

Done criteria:
- Coverage target met
- Documentation matches shipped behavior exactly

## 7) Implementation Tracking Protocol

To avoid losing scope clarity:

1. Keep this document updated at each phase transition.
2. Update `packages/PolicyEngine/IMPLEMENTATION_SUMMARY.md` after every meaningful change.
3. Maintain two explicit lists in summary:
   - "Implemented in this phase"
   - "Deferred / On Hold"
4. Reject PRs that add out-of-scope v2 runtime features in v1 branch.

## 8) Risks and Mitigations

- Risk: scope creep into threshold runtime logic  
  Mitigation: enforce phase gates and explicit On Hold checklist.

- Risk: duplicated domain logic across packages  
  Mitigation: all policy decisions must route through `PolicyEngineInterface`.

- Risk: hidden nondeterminism  
  Mitigation: deterministic test suite and strict tie-break rules.

## 9) Completion Summary

Implemented:

- Package scaffold and root autoload wiring
- Contracts/enums/domain/value objects
- Deterministic evaluator + validator
- Threshold runtime comparators (`GT`, `GTE`, `LT`, `LTE`, `BETWEEN`)
- Unit test suite passing with coverage above target threshold
- Package docs (`README`, `REQUIREMENTS`, `IMPLEMENTATION_SUMMARY`) synced with delivered scope

Deferred:

- Persistence adapters
- Admin policy authoring UI
