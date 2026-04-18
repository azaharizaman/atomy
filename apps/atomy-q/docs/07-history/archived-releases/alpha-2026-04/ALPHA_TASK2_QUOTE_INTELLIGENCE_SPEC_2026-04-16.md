# Alpha Task 2 Quote Intelligence Spec

## Document Control

- **Task:** Section 9, Task 2 - Replace Mock Quote Intelligence Binding
- **Date:** 2026-04-16
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 2 closes the highest-risk quote-intelligence credibility gap in alpha: live quote ingestion must not depend on classes named `Mock*` while presenting itself as production-like intelligence.

The alpha release will ship with a deterministic processor path as the active and supported quote-intelligence mode. At the same time, the system must define a dormant LLM-backed mode now so that future provider integration can be added without redesigning the ingestion boundary, environment contract, or operator expectations.

This spec defines the alpha-safe behavior, configuration contract, naming expectations, failure semantics, documentation updates, and verification evidence for that work.

## 2. Current State

The current API app binds:

- `OrchestratorContentProcessorInterface` to `App\\Adapters\\QuotationIntelligence\\MockContentProcessor`
- `SemanticMapperInterface` to `App\\Adapters\\QuotationIntelligence\\MockSemanticMapper`

Those adapters are already deterministic and test-friendly, but their naming and release posture are misleading:

- they are bound in the live application container
- they do not clearly communicate that the alpha path is deterministic, not LLM-backed
- the API environment docs do not yet define a quote-intelligence mode contract
- the future LLM mode is implied rather than explicitly modeled

## 3. Scope

### In scope

- Replace misleading `Mock*` quote-intelligence naming in live bindings.
- Make deterministic quote intelligence the supported alpha runtime mode.
- Define a dormant `llm` runtime mode and its required environment contract.
- Keep the ingestion pipeline interface-first through the existing quote-intelligence contracts.
- Ensure quote ingestion failures are visible, sanitized, and operationally traceable.
- Verify persisted normalization source lines carry the metadata needed by the alpha normalization and comparison flow.
- Update API docs and environment examples to describe both modes truthfully.
- Add or update feature tests covering deterministic success and dormant-LLM misconfiguration behavior.

### Out of scope

- Shipping a real external LLM provider integration in this task.
- Adding retries, fallback chains, or multi-provider routing beyond what is needed to define the dormant contract.
- Expanding quote-intelligence output shape beyond the metadata already required for normalization review and comparison.
- Changing WEB behavior outside of what is already driven by existing API ingestion states.

## 4. Alpha Decision

Alpha will support two named processor modes:

- `deterministic`: active, supported, and required for current alpha readiness
- `llm`: dormant, explicitly configured, and unavailable until external provider settings are supplied and a real adapter is implemented

The alpha release must default to `deterministic`.

The alpha release must not silently claim AI-backed processing when deterministic mode is active.

The alpha release must not silently fall back from `llm` mode to `deterministic` mode in live operation. If `llm` mode is selected without a valid provider configuration, ingestion must fail in a controlled and sanitized way.

## 5. Architecture

### 5.1 Runtime boundary

Task 2 must continue to use the existing orchestration boundary:

- `OrchestratorContentProcessorInterface`
- `SemanticMapperInterface`
- `QuotationIntelligenceCoordinatorInterface`
- `QuoteIngestionOrchestrator`

The application container is the place where runtime mode selection is made.

### 5.2 Deterministic alpha path

The current deterministic processor behavior should remain the functional baseline for alpha, but it must be renamed so the codebase and docs stop calling it `Mock` in live mode.

The deterministic path may continue using:

- RFQ line-item-aware extraction heuristics
- rule-based taxonomy mapping
- stable confidence values
- CI-safe behavior with no external network dependency

The deterministic path must remain fully testable with local and CI SQLite feature coverage.

### 5.3 Dormant LLM path

Task 2 must define a dormant LLM-backed mode contract now, even though it will not be operational for this milestone.

That means the code and docs must establish:

- a named `llm` mode
- the required environment variables for provider activation
- the requirement that this mode is invalid until provider settings are fully supplied
- the requirement that failures in this mode are sanitized for API consumers and traceable for operators

This task does not require a working provider adapter, but it must leave the system ready for one to be added without changing the ingestion contract.

## 6. Configuration Contract

The API environment contract for quote intelligence must explicitly define:

- `QUOTE_INTELLIGENCE_MODE=deterministic|llm`
- `QUOTE_INTELLIGENCE_LLM_PROVIDER`
- `QUOTE_INTELLIGENCE_LLM_MODEL`
- `QUOTE_INTELLIGENCE_LLM_BASE_URL`
- `QUOTE_INTELLIGENCE_LLM_API_KEY`
- `QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS`

### Required semantics

- If `QUOTE_INTELLIGENCE_MODE` is absent, alpha defaults to `deterministic`.
- `deterministic` mode must not require any LLM env values.
- `llm` mode must be treated as invalid when any required provider fields are missing or blank.
- Invalid `llm` configuration must not degrade silently into deterministic processing.
- Documentation must describe `llm` mode as dormant until a real provider adapter is implemented and configured.

## 7. Persistence And Data Requirements

After successful processing, normalization source lines must continue to persist the metadata required by the alpha normalization review and comparison flow.

The Task 2 acceptance shape requires persisted source lines to include, directly or through existing related fields:

- tenant id
- RFQ id
- quote submission id
- vendor identity or vendor name context
- RFQ line association
- source description
- source quantity
- source UoM
- source unit price
- mapping confidence
- taxonomy code
- mapping version
- evidence metadata needed to explain the normalization result

Task 2 may keep using the current storage structure if those requirements are already satisfied, but the implementation must verify and test that they remain true after the binding cleanup.

## 8. Failure Handling

Quote ingestion failures in Task 2 must satisfy all of the following:

1. The quote submission is marked `failed`.
2. The API-visible failure message is sanitized and generic.
3. Logs retain operational detail needed to diagnose the failure path.
4. `llm` mode misconfiguration is surfaced as a controlled quote-intelligence failure, not as silent fallback behavior.

### Sanitization rule

User-visible or API-visible quote submission failure messages must not expose:

- raw provider credentials
- raw upstream exception text
- raw endpoint details that are not needed by end users

This rule applies both to orchestrator failure handling and any job-level terminal failure path for exhausted retries.

## 9. Documentation Requirements

Task 2 must update the affected package documentation after the code changes.

Required doc updates:

- `apps/atomy-q/API/.env.example`
- `apps/atomy-q/API/README.md`
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`

Documentation must clearly say:

- alpha currently runs deterministic quote intelligence
- deterministic mode is intentional and honest, not hidden mock behavior
- LLM mode is designed now but not active until provider wiring and external settings exist

## 10. Acceptance Criteria

Task 2 is complete only when all of the following are true:

1. Live API bindings no longer use `MockContentProcessor` or `MockSemanticMapper` as the active alpha-facing names.
2. The application defines explicit quote-intelligence runtime mode selection.
3. Alpha defaults to `deterministic` mode.
4. `llm` mode is documented and config-gated, but not falsely presented as available without provider setup.
5. Selecting misconfigured `llm` mode causes controlled, sanitized ingestion failure with operational traceability.
6. Deterministic processing continues to persist normalization source lines with the metadata required by alpha normalization and comparison flows.
7. API docs and `.env.example` describe both modes truthfully.
8. `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` is updated to reflect the shipped Task 2 behavior.

## 11. Verification

Minimum verification evidence for Task 2:

- `cd apps/atomy-q/API && php artisan test --filter QuoteIngestionIntelligenceTest`
- `cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest`

If implementation touches broader quote-ingestion or normalization workflow behavior, run the relevant additional alpha tests and record the results in the updated documentation.

## 12. Risks And Non-Goals

### Risks accepted for this task

- The alpha release may ship without a live LLM provider as long as deterministic mode is named honestly and documented clearly.
- The dormant `llm` mode may fail fast until a real adapter and credentials are available.

### Non-goals

- This task does not promise semantic parity between deterministic and future LLM-backed processing.
- This task does not promise automatic fallback behavior between processor modes.
- This task does not promise production provider uptime, cost controls, or rate-limit handling beyond defining the contract required for future work.
