# Atomy-Q AI Foundation And Runtime Governance Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish the global AI operating contract for alpha: provider-backed by default, truthful when degraded, and consumable by every API endpoint and WEB surface through one authoritative runtime model.

**Architecture:** Layer 1 defines provider-neutral AI runtime vocabulary and procurement-capability metadata. Layer 2 aggregates endpoint-group health into application-facing capability availability. Layer 3 adapts a single globally selected provider configuration, exposes status routes, and gives the WEB app one capability-aware source of truth for rendering manual continuity or unavailable states.

**Tech Stack:** PHP 8.3, Laravel, Nexus packages, Next.js/React, TypeScript, TanStack Query, PHPUnit, Vitest.

---

## Scope

- Global `AI_MODE` semantics: `off`, `provider`, `deterministic`
- Legacy `llm` compatibility alias during transition
- Capability groups and feature registry
- Endpoint-group health aggregation
- Public AI status API for pre-auth and post-auth rendering
- WEB AI provider, hook, and reusable unavailable-state primitives
- Env/config contract for a single globally selected provider
- OpenRouter as the alpha default provider, with Hugging Face as a supported alternative

## Layer Ownership

- **Layer 1**
  - `packages/MachineLearning`: AI mode, health, endpoint-group, provider config, provider result, capability definition, runtime snapshot, health exception mapping.
  - `packages/ProcurementML`: procurement-facing feature keys and procurement-capability descriptors if they would otherwise leak Atomy-Q-specific strings into framework code.
  - `packages/AuditLogger`, `packages/Audit`, `packages/Notifier`, `packages/Idempotency`, `packages/Outbox`: shared evidence, alert, retry, and publication contracts used by later plans.
- **Layer 2**
  - `orchestrators/IntelligenceOperations`: capability availability coordination, health aggregation, operator-facing status summary, endpoint-group dependency resolution.
- **Layer 3**
  - `apps/atomy-q/API`: env parsing, provider-specific config adapters, health probes, public status endpoint, controller gating concern, OpenAPI.
  - `apps/atomy-q/WEB`: AI status query, context provider, capability helper utilities, status chips, unavailable callouts.

## File Structure

- Create: `packages/MachineLearning/src/Enums/AiMode.php`
- Create: `packages/MachineLearning/src/Enums/AiHealth.php`
- Create: `packages/MachineLearning/src/Enums/AiCapabilityGroup.php`
- Create: `packages/MachineLearning/src/Enums/AiFallbackUiMode.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiEndpointConfig.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiCapabilityDefinition.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiEndpointHealthSnapshot.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiRuntimeSnapshot.php`
- Create: `packages/MachineLearning/src/Contracts/AiCapabilityCatalogInterface.php`
- Create: `packages/MachineLearning/src/Contracts/AiRuntimeStatusProviderInterface.php`
- Create: `packages/MachineLearning/src/Contracts/AiHealthProbeInterface.php`
- Create: `packages/MachineLearning/tests/Unit/...`
- Modify: `packages/MachineLearning/README.md`
- Modify: `packages/MachineLearning/IMPLEMENTATION_SUMMARY.md`
- Create or modify: `packages/ProcurementML/src/...` for procurement AI feature keys if needed

- Create: `orchestrators/IntelligenceOperations/src/DTOs/AiCapabilityStatus.php`
- Create: `orchestrators/IntelligenceOperations/src/DTOs/AiStatusSnapshot.php`
- Create: `orchestrators/IntelligenceOperations/src/Contracts/AiStatusCoordinatorInterface.php`
- Create: `orchestrators/IntelligenceOperations/src/Coordinators/AiStatusCoordinator.php`
- Create: `orchestrators/IntelligenceOperations/tests/Unit/...`
- Modify: `orchestrators/IntelligenceOperations/IMPLEMENTATION_SUMMARY.md`

- Modify: `apps/atomy-q/API/config/atomy.php`
- Modify: `apps/atomy-q/API/config/services.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderEndpointRegistry.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderHealthProbe.php`
- Create if needed: provider-specific adapters such as `OpenRouterEndpointRegistry.php`, `OpenRouterHealthProbe.php`, `HuggingFaceEndpointRegistry.php`, and `HuggingFaceHealthProbe.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/AtomyAiCapabilityCatalog.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AiStatusController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/Concerns/InteractsWithAiAvailability.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/API/openapi/openapi.json`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/AiStatusApiTest.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/AiStatusVisibilityTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- Create: `apps/atomy-q/WEB/src/lib/ai-capabilities.ts`
- Create: `apps/atomy-q/WEB/src/lib/ai-status.ts`
- Create: `apps/atomy-q/WEB/src/providers/ai-provider.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-status.ts`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-status-chip.tsx`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-unavailable-callout.tsx`
- Modify: `apps/atomy-q/WEB/src/app/layout.tsx`
- Modify: `apps/atomy-q/WEB/src/components/layout/main-sidebar-nav.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-status.test.ts`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-unavailable-callout.test.tsx`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

## Task 1: Define Layer 1 Runtime Contracts

- [ ] Write failing tests for AI mode parsing, capability definitions, endpoint-group health, and runtime snapshot serialization.
- [ ] Implement provider-neutral enums and value objects in `packages/MachineLearning`.
- [ ] Keep provider names out of Layer 1 contracts; use endpoint-group and provider-neutral terminology.
- [ ] Add procurement-facing feature keys only where they improve cross-layer consistency without pulling app routes or UI terms into Layer 1.

## Task 2: Build Capability Aggregation In Layer 2

- [ ] Add `AiStatusCoordinator` to `orchestrators/IntelligenceOperations` that combines mode, endpoint-group health, and feature fallback policy into one snapshot.
- [ ] Model capability-level results with explicit values for `available`, `degraded`, `disabled`, and `unavailable`.
- [ ] Make the coordinator return reasons and operator-safe diagnostic metadata without leaking secrets or raw provider credentials.
- [ ] Add unit tests for partial endpoint failures, full config disablement, and deterministic fallback mode.

## Task 3: Add API Runtime Adapters And Public Status Contract

- [ ] Add API config parsing for:
  - `AI_MODE`
  - `AI_PROVIDER`
  - `AI_PROVIDER_NAME`
  - `AI_DOCUMENT_ENDPOINT`
  - `AI_NORMALIZATION_ENDPOINT`
  - `AI_SOURCING_RECOMMENDATION_ENDPOINT`
  - `AI_COMPARISON_AWARD_ENDPOINT`
  - `AI_INSIGHT_ENDPOINT`
  - `AI_GOVERNANCE_ENDPOINT`
  - matching auth token and timeout settings
- [ ] Implement a provider-selectable endpoint registry and health probe adapter in Layer 3.
- [ ] Enforce that one provider selection applies globally to all endpoint groups in the environment.
- [ ] Expose `GET /api/v1/ai/status` as a public route so the WEB app can render before auth is established.
- [ ] Return a stable envelope that includes:
  - global mode
  - global health
  - per-capability availability
  - per-endpoint-group health
  - operator-safe reason codes
- [ ] Add a reusable controller concern for AI gating so later plans do not scatter status logic through controllers.

## Task 4: Add WEB AI Status Consumption And Shared UX Primitives

- [ ] Implement `use-ai-status` and a top-level AI provider.
- [ ] Add helpers that answer:
  - whether a feature is available,
  - whether to hide AI controls,
  - whether to show an unavailable message,
  - which message key to render.
- [ ] Add reusable UI primitives for status chips and unavailable callouts.
- [ ] Wire the status provider into the app shell without blocking non-AI navigation if the status request fails.

## Task 5: Documentation And Verification

- [ ] Update package and app implementation summaries with the new runtime contract.
- [ ] Update any environment documentation that explains the alpha provider topology.
- [ ] Run:
```bash
./vendor/bin/phpunit packages/MachineLearning/tests orchestrators/IntelligenceOperations/tests apps/atomy-q/API/tests/Feature/Api/V1/AiStatusApiTest.php
cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-ai-status.test.ts src/components/ai/ai-unavailable-callout.test.tsx
```
- [ ] Confirm the status endpoint behaves correctly for `off`, `provider`, and `deterministic` modes before starting Plan 2.

## Exit Criteria

- Every later plan can ask one API for AI runtime truth.
- WEB has one capability-aware gating primitive instead of ad hoc env checks.
- The system can distinguish `disabled by config` from `runtime unavailable`.
- No provider transport detail leaks into Layer 1 contracts.
