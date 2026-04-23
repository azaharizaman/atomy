# Atomy-Q Global AI Fallback Implementation Plan

> **Superseded:** Use [docs/superpowers/plans/2026-04-23-atomy-q-ai-plan-index.md](docs/superpowers/plans/2026-04-23-atomy-q-ai-plan-index.md) and its six dependency-ordered implementation plans. This single-plan document reflects the earlier, narrower fallback-only posture and is no longer the authoritative execution entry point after the AI-first alpha redesign.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Atomy-Q AI-backed at its core while keeping every RFQ workflow usable when AI is disabled, degraded, or unavailable.

**Architecture:** Keep the product split by responsibility. Layer 1 (`packages/MachineLearning`) owns the global AI vocabulary, feature definitions, and runtime state contracts. Layer 2 (`orchestrators/IntelligenceOperations`) composes those contracts into a single authoritative AI status snapshot. Layer 3 (`apps/atomy-q/API` and `apps/atomy-q/WEB`) adapts environment/configuration into the status snapshot, exposes a public AI status endpoint, and hides or disables only the AI-only surfaces while preserving the manual RFQ paths.

**Tech Stack:** PHP 8.3, Laravel, Nexus packages/orchestrators, Next.js/React, TypeScript, TanStack Query, PHPUnit, Vitest, Playwright.

---

## File Structure

- Create: `packages/MachineLearning/src/Enums/AiMode.php`
- Create: `packages/MachineLearning/src/Enums/AiHealth.php`
- Create: `packages/MachineLearning/src/Enums/AiFeatureKey.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiCapabilityDefinition.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiRuntimeState.php`
- Create: `packages/MachineLearning/src/Contracts/AiCapabilityCatalogInterface.php`
- Create: `packages/MachineLearning/src/Contracts/AiStatusProviderInterface.php`
- Create: `packages/MachineLearning/tests/Unit/Enums/AiModeTest.php`
- Create: `packages/MachineLearning/tests/Unit/ValueObjects/AiCapabilityDefinitionTest.php`
- Create: `packages/MachineLearning/tests/Unit/ValueObjects/AiRuntimeStateTest.php`
- Modify: `packages/MachineLearning/IMPLEMENTATION_SUMMARY.md`
- Modify: `packages/MachineLearning/README.md`

- Create: `orchestrators/IntelligenceOperations/src/DTOs/AiStatusSnapshot.php`
- Create: `orchestrators/IntelligenceOperations/src/Contracts/AiStatusCoordinatorInterface.php`
- Create: `orchestrators/IntelligenceOperations/src/Coordinators/AiStatusCoordinator.php`
- Create: `orchestrators/IntelligenceOperations/tests/Unit/Coordinators/AiStatusCoordinatorTest.php`
- Modify: `orchestrators/IntelligenceOperations/IMPLEMENTATION_SUMMARY.md`

- Modify: `apps/atomy-q/API/config/atomy.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/EnvAiStatusProvider.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/AtomyAiCapabilityCatalog.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AiStatusController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/Concerns/InteractsWithAiAvailability.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RecommendationController.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/AiStatusApiTest.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/RecommendationAiGateTest.php`
- Modify: `apps/atomy-q/API/README.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- Create: `apps/atomy-q/WEB/src/lib/ai-mode.ts`
- Create: `apps/atomy-q/WEB/src/lib/ai-capabilities.ts`
- Create: `apps/atomy-q/WEB/src/providers/ai-provider.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-status.ts`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-unavailable-callout.tsx`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-status-chip.tsx`
- Modify: `apps/atomy-q/WEB/src/app/layout.tsx`
- Modify: `apps/atomy-q/WEB/src/components/layout/header.tsx`
- Modify: `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-status.test.ts`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-unavailable-callout.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.test.tsx`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/README.md`

---

## Plan Sequence

1. Build the Layer 1 AI vocabulary and runtime-state contracts in `packages/MachineLearning`.
2. Build the Layer 2 AI status coordinator in `orchestrators/IntelligenceOperations`.
3. Add the API AI status endpoint and AI-only endpoint gates in `apps/atomy-q/API`.
4. Add the WEB AI provider, status hook, and reusable unavailable-state components.
5. Update every AI-capable screen to either hide AI-only controls or show explicit unavailable messaging.
6. Run package, API, WEB, and E2E verification, then update the implementation summaries and README files.

---

## Dependency Rules

- `AI_MODE` is the global alpha switch for AI-backed behavior. It is not a tenant setting in alpha.
- `QUOTE_INTELLIGENCE_MODE` continues to control the quote ingestion and normalization pipeline internals. Do not collapse the two into one env variable.
- Manual RFQ workflows must remain fully usable when `AI_MODE=off`.
- AI-only surfaces must not fabricate success payloads when AI is off or unavailable.
- Do not use `AlphaDeferredScreen` for AI-off states. AI-off needs a capability-aware unavailable component, not an alpha-release copy.
- The public AI status endpoint must be readable without tenant auth so the WEB app can render state before a user session is established.

---

## Task 1: Layer 1 AI Vocabulary

**Files:**
- Create: `packages/MachineLearning/src/Enums/AiMode.php`
- Create: `packages/MachineLearning/src/Enums/AiHealth.php`
- Create: `packages/MachineLearning/src/Enums/AiFeatureKey.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiCapabilityDefinition.php`
- Create: `packages/MachineLearning/src/ValueObjects/AiRuntimeState.php`
- Create: `packages/MachineLearning/src/Contracts/AiCapabilityCatalogInterface.php`
- Create: `packages/MachineLearning/src/Contracts/AiStatusProviderInterface.php`
- Create: `packages/MachineLearning/tests/Unit/Enums/AiModeTest.php`
- Create: `packages/MachineLearning/tests/Unit/ValueObjects/AiCapabilityDefinitionTest.php`
- Create: `packages/MachineLearning/tests/Unit/ValueObjects/AiRuntimeStateTest.php`
- Modify: `packages/MachineLearning/README.md`
- Modify: `packages/MachineLearning/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Write the failing package tests**

Create tests that assert the global vocabulary is stable and explicit.

The tests must cover these cases:
- `AiMode::from('off')`, `AiMode::from('deterministic')`, and `AiMode::from('llm')` are accepted.
- Unknown values throw a domain exception instead of silently coercing.
- `AiHealth` distinguishes `disabled`, `healthy`, `degraded`, and `unavailable`.
- `AiCapabilityDefinition` carries `featureKey`, `requiresAi`, `hasManualFallback`, `fallbackUiMode`, and `degradationMessageKey`.
- `AiRuntimeState` stores the current mode, health, and reason string without needing framework state.

Run:
```bash
cd packages/MachineLearning && ./vendor/bin/phpunit tests/Unit/Enums/AiModeTest.php tests/Unit/ValueObjects/AiCapabilityDefinitionTest.php tests/Unit/ValueObjects/AiRuntimeStateTest.php
```
Expected: FAIL because the new types do not exist yet.

- [ ] **Step 2: Implement the Layer 1 contracts and value objects**

Define the global AI vocabulary in pure PHP only. The package must not read env vars directly.

The canonical feature keys should cover at least:
- `quote_ingestion`
- `quote_normalization`
- `comparison`
- `award`
- `vendor_recommendation`
- `rfq_insights`
- `dashboard_ai_insights`
- `recommendation_endpoints`

Use immutable objects and backed enums only. The package must stay framework-agnostic.

- [ ] **Step 3: Run the package tests again**

Run:
```bash
cd packages/MachineLearning && ./vendor/bin/phpunit tests/Unit/Enums/AiModeTest.php tests/Unit/ValueObjects/AiCapabilityDefinitionTest.php tests/Unit/ValueObjects/AiRuntimeStateTest.php
```
Expected: PASS.

- [ ] **Step 4: Update the package docs**

Document the new AI vocabulary in `README.md` and record the behavior in `IMPLEMENTATION_SUMMARY.md`.

- [ ] **Step 5: Commit the Layer 1 package change**

Use a focused commit containing only the new AI vocabulary and its package tests.

---

## Task 2: Layer 2 AI Status Coordinator

**Files:**
- Create: `orchestrators/IntelligenceOperations/src/DTOs/AiStatusSnapshot.php`
- Create: `orchestrators/IntelligenceOperations/src/Contracts/AiStatusCoordinatorInterface.php`
- Create: `orchestrators/IntelligenceOperations/src/Coordinators/AiStatusCoordinator.php`
- Create: `orchestrators/IntelligenceOperations/tests/Unit/Coordinators/AiStatusCoordinatorTest.php`
- Modify: `orchestrators/IntelligenceOperations/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Write the failing coordinator tests**

The coordinator tests must prove these cases:
- `AI_MODE=off` produces `health=disabled` and keeps the capability catalog intact.
- `AI_MODE=deterministic` produces `health=healthy` and marks AI-assisted features available.
- `AI_MODE=llm` with missing provider/runtime support produces `health=unavailable`.
- The snapshot exposes consistent feature metadata for all registered AI-capable surfaces.

Run:
```bash
cd orchestrators/IntelligenceOperations && ./vendor/bin/phpunit tests/Unit/Coordinators/AiStatusCoordinatorTest.php
```
Expected: FAIL because the coordinator does not exist yet.

- [ ] **Step 2: Implement the coordinator and status DTO**

Make `AiStatusCoordinator` depend on the Layer 1 catalog and runtime-state provider.

The status snapshot should serialize to a payload shaped like this:

```json
{
  "data": {
    "mode": "off",
    "health": "disabled",
    "reason": "global_ai_disabled_by_env",
    "features": {
      "vendor_recommendation": {
        "requires_ai": true,
        "has_manual_fallback": false,
        "fallback_ui_mode": "show_unavailable_message"
      }
    }
  }
}
```

Keep the coordinator pure. It must not read env vars or touch Laravel.

- [ ] **Step 3: Run the coordinator tests again**

Run:
```bash
cd orchestrators/IntelligenceOperations && ./vendor/bin/phpunit tests/Unit/Coordinators/AiStatusCoordinatorTest.php
```
Expected: PASS.

- [ ] **Step 4: Update the orchestrator summary**

Record the status-coordinator contract in `IMPLEMENTATION_SUMMARY.md`, including the difference between `disabled` and `unavailable`.

- [ ] **Step 5: Commit the Layer 2 change**

Use a focused commit containing the coordinator, DTO, tests, and summary update.

---

## Task 3: API AI Status Endpoint and AI-only Gating

**Files:**
- Modify: `apps/atomy-q/API/config/atomy.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/EnvAiStatusProvider.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/AtomyAiCapabilityCatalog.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AiStatusController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/Concerns/InteractsWithAiAvailability.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RecommendationController.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/AiStatusApiTest.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/RecommendationAiGateTest.php`
- Modify: `apps/atomy-q/API/README.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Write the failing API feature tests**

Cover these cases:
- `GET /api/v1/ai/status` is public and returns the current mode, health, reason, and capability manifest.
- `POST /api/v1/rfqs/{rfqId}/vendor-recommendations` returns a structured `503` response when AI is disabled.
- `GET /api/v1/recommendations/{runId}` no longer returns synthetic empty payloads; it either returns real AI-backed data or a structured unavailable response.

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/AiStatusApiTest.php tests/Feature/Api/V1/VendorRecommendationAiGateTest.php tests/Feature/Api/V1/RecommendationAiGateTest.php
```
Expected: FAIL.

- [ ] **Step 2: Wire the API adapter classes and public status controller**

Bind the new Layer 1 contracts in `AppServiceProvider` using framework-specific adapters.

The `EnvAiStatusProvider` must translate the env/config state into the runtime-state contract using `AI_MODE` as the top-level switch.

The `AtomyAiCapabilityCatalog` must return the canonical feature registry. It should mark the AI-only surfaces as requiring AI and mark the manual-fallback RFQ surfaces as hideable instead of globally blocked.

`AiStatusController` should be a thin adapter that calls the Layer 2 coordinator and returns JSON from the snapshot.

- [ ] **Step 3: Gate the AI-only endpoints**

Use the shared AI availability concern to keep AI-only endpoints from returning fake success payloads.

Required behavior:
- When `AI_MODE=off`, `vendor-recommendations` must respond with a structured `503` and `code=ai_disabled`.
- When the runtime is expected to be live but the provider is unavailable, return `code=ai_unavailable`.
- `RecommendationController` must stop returning hard-coded arrays and must never fabricate `completed` or `pending` statuses.

Manual RFQ routes such as quote upload, normalization, comparison freezing, and award creation should remain callable and continue using their deterministic/manual logic.

- [ ] **Step 4: Run the API tests again**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/AiStatusApiTest.php tests/Feature/Api/V1/VendorRecommendationAiGateTest.php tests/Feature/Api/V1/RecommendationAiGateTest.php
```
Expected: PASS.

- [ ] **Step 5: Update the API docs and implementation summary**

Document the new public AI status route, the `AI_MODE` contract, and the `503` AI-only fallback semantics in `README.md` and `IMPLEMENTATION_SUMMARY.md`.

- [ ] **Step 6: Commit the API change**

Use a focused commit containing the adapters, controller, route, tests, and documentation updates.

---

## Task 4: WEB AI Provider, Status Hook, and Shared Unavailable Components

**Files:**
- Create: `apps/atomy-q/WEB/src/lib/ai-mode.ts`
- Create: `apps/atomy-q/WEB/src/lib/ai-capabilities.ts`
- Create: `apps/atomy-q/WEB/src/providers/ai-provider.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-status.ts`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-unavailable-callout.tsx`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-status-chip.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-status.test.ts`
- Create: `apps/atomy-q/WEB/src/components/ai/ai-unavailable-callout.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/layout.tsx`

- [ ] **Step 1: Write the failing WEB provider and hook tests**

The provider and hook tests must prove:
- the UI starts from `NEXT_PUBLIC_AI_MODE` before the API status probe resolves,
- the API status response replaces the env fallback once loaded,
- AI-only features are reported as unavailable when the status endpoint says `off`,
- manual-fallback surfaces remain enabled when AI is off,
- the unavailable component renders the correct copy for `disabled` versus `unavailable`.

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-ai-status.test.ts src/components/ai/ai-unavailable-callout.test.tsx
```
Expected: FAIL.

- [ ] **Step 2: Implement the AI provider and reusable helpers**

Add a top-level AI provider next to the existing query/auth providers in `src/app/layout.tsx`.

The provider must:
- read `NEXT_PUBLIC_AI_MODE` immediately,
- fetch `NEXT_PUBLIC_AI_STATUS_PATH` through the existing API client,
- expose `mode`, `health`, `reason`, and per-feature availability to consumers,
- keep the app usable if the AI status probe fails by falling back to the env mirror.

`useAiStatus` should be the single hook that the screens use. `ai-capabilities.ts` should hold the feature-key constants and the hide-vs-message policy map.

- [ ] **Step 3: Run the hook and component tests again**

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-ai-status.test.ts src/components/ai/ai-unavailable-callout.test.tsx
```
Expected: PASS.

- [ ] **Step 4: Commit the WEB AI foundation**

Commit the provider, hook, helper, and reusable component layer before touching any pages.

---

## Task 5: WEB Screen Fallbacks and AI-only Surface Cleanup

**Files:**
- Modify: `apps/atomy-q/WEB/src/components/layout/header.tsx`
- Modify: `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.test.tsx`

- [ ] **Step 1: Update the AI-only hook so it never calls the recommendation endpoint when AI is off**

`use-vendor-recommendations.ts` must consult `useAiStatus` and disable the query when the vendor-recommendation capability is unavailable.

This hook is the network gate. It must not fetch the AI-only endpoint just to show an unavailable message.

- [ ] **Step 2: Update the header and dashboard entry points**

The header AI button must not imply AI is available when the status endpoint says it is off or unavailable.

The dashboard AI badge must become capability-aware. If the feature is unavailable, the page should not announce “AI insights ready”.

- [ ] **Step 3: Update the RFQ workspace surfaces**

The RFQ insights sidebar must keep the manual context cards visible but replace the AI insights card with an unavailable state when AI is off.

The vendor-selection panel must:
- hide recommended badges and explanation drawers when vendor recommendation is unavailable,
- preserve manual vendor selection and save behavior,
- avoid auto-prefilling from AI recommendations when the recommendation query is disabled.

The comparison-run detail page must:
- keep the matrix and readiness information visible,
- hide or replace the AI recommendation overlay in each cluster when AI is off,
- not present AI-generated explanation copy as if it were still live.

The award page must:
- keep the award creation and signoff workflow functional,
- hide any AI recommendation labels or copy,
- keep the manual award selection path intact.

The normalization page must:
- keep manual mapping and freeze controls available,
- hide any AI-only assist controls,
- not route the user into an AI-dependent branch when the status says AI is off.

- [ ] **Step 4: Run the screen tests again**

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx \
  src/app/'(dashboard)'/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx \
  src/app/'(dashboard)'/rfqs/[rfqId]/award/page.test.tsx \
  src/app/'(dashboard)'/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx \
  src/app/'(dashboard)'/page.test.tsx
```
Expected: PASS.

- [ ] **Step 5: Commit the WEB screen work**

Use a focused commit for the UI surface cleanup so the provider/hook changes and the page changes can be reviewed separately if needed.

---

## Task 6: Verification, Docs, and Release Handoff

**Files:**
- Modify: `packages/MachineLearning/IMPLEMENTATION_SUMMARY.md`
- Modify: `orchestrators/IntelligenceOperations/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/API/README.md`
- Modify: `apps/atomy-q/WEB/README.md`

- [ ] **Step 1: Run the full targeted verification set**

Run:
```bash
cd /home/azaharizaman/dev/atomy
cd packages/MachineLearning && ./vendor/bin/phpunit tests/Unit/Enums/AiModeTest.php tests/Unit/ValueObjects/AiCapabilityDefinitionTest.php tests/Unit/ValueObjects/AiRuntimeStateTest.php
cd /home/azaharizaman/dev/atomy/orchestrators/IntelligenceOperations && ./vendor/bin/phpunit tests/Unit/Coordinators/AiStatusCoordinatorTest.php
cd /home/azaharizaman/dev/atomy/apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/AiStatusApiTest.php tests/Feature/Api/V1/VendorRecommendationAiGateTest.php tests/Feature/Api/V1/RecommendationAiGateTest.php
cd /home/azaharizaman/dev/atomy/apps/atomy-q/WEB && npx vitest run src/hooks/use-ai-status.test.ts src/components/ai/ai-unavailable-callout.test.tsx
cd /home/azaharizaman/dev/atomy/apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/award/page.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx src/app/'(dashboard)'/page.test.tsx
```
Expected: PASS.

- [ ] **Step 2: Run the browser regression for the RFQ fallback path**

Run the alpha RFQ browser journey with `NEXT_PUBLIC_AI_MODE=off` and confirm the manual paths still work while AI-only surfaces are hidden or unavailable.

Use the existing Playwright RFQ alpha journey as the base regression and add an AI-off pass if one does not already exist.

- [ ] **Step 3: Update the summaries and README files**

Document the final behavior in the package/orchestrator implementation summaries and both app READMEs.

The docs must explain:
- `AI_MODE` is global for alpha,
- manual RFQ paths still work without AI,
- AI-only surfaces return a structured unavailable state,
- `NEXT_PUBLIC_AI_STATUS_PATH` is the public contract the WEB reads.

- [ ] **Step 4: Commit the release-handoff docs**

Use a final docs commit after the verification run so the implementation plan, code, and operational docs stay aligned.

---

## Acceptance Criteria

- The app can run with `AI_MODE=off` and still complete quote ingestion, normalization, comparison, and award workflows manually.
- Vendor recommendation and other AI-only surfaces do not fabricate results when AI is off.
- The WEB hides AI-only controls where manual fallback exists and shows a clear unavailable message where it does not.
- The API exposes a public AI status endpoint that distinguishes config-disabled AI from runtime-unavailable AI.
- The implementation is covered by package, API, WEB, and browser tests.
