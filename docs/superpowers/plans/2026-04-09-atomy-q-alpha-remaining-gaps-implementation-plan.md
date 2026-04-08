# Atomy-Q Alpha Remaining Gaps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close all re-opened and open alpha-critical gaps with small, verifiable PR slices while preserving Nexus Layer 1/2/3 boundaries.

**Architecture:** Execute by risk-first phases: (1) gate recovery and dependency/runtime integrity, (2) mock leakage removal on golden path, (3) core domain closures (awards/vendors/identity), (4) AI normalization productionization after model decision. Each phase must end with green verification commands and docs updates.

**Tech Stack:** Laravel 12 (API), Next.js 16 + React Query (WEB), Nexus Layer 1/2/3 packages and adapters, PHPUnit, Vitest, ESLint, Composer.

---

### Task 1: Phase 1 PR - Gate Recovery + RFQ Lifecycle Wiring

**Files:**
- Modify: `apps/atomy-q/API/composer.lock`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/eslint.config.mjs`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`

- [x] **Step 1: Reproduce failing gates**

Run:
```bash
cd apps/atomy-q/WEB && npm run build
cd apps/atomy-q/WEB && npm run lint
cd apps/atomy-q/API && php artisan test --filter "RfqLifecycleMutationTest"
```
Expected: build/lint fail + RFQ lifecycle test fails with `SourcingOperationsCoordinator` resolution issue.

- [x] **Step 2: Restore Composer lock parity for sourcing orchestrator**

Run:
```bash
cd apps/atomy-q/API && composer update nexus/sourcing-operations
```
Expected: `composer.lock` includes `nexus/sourcing-operations`.

- [x] **Step 3: Fix WEB type-check compile break**

Change comparison run detail page so `SnapshotLinesCard` receives `ComparisonRunSnapshot | null` (never `undefined`).

- [x] **Step 4: Make lint gate green for intentional generated code**

Adjust ESLint config to ignore generated API client artifacts (`src/generated/api/**`) and remove explicit `any` in award page test.

- [x] **Step 5: Verify phase 1 gates**

Run:
```bash
cd apps/atomy-q/API && php artisan test --filter "RfqLifecycleMutationTest|AwardWorkflowTest|IdentityGap7Test"
cd apps/atomy-q/WEB && npm run build
cd apps/atomy-q/WEB && npm run lint
```
Expected: all pass.

### Task 2: Phase 2 PR - Golden-Path Mock Leakage Elimination

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfqs.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfqs.test.ts`
- Modify: `apps/atomy-q/WEB/README.md`
- Modify: `apps/atomy-q/docs/ALPHA_REMAINING_GAPS_IMPLEMENTATION_SPEC_2026-04-09.md`

- [x] **Step 1: Write failing tests for live-mode no-fallback behavior**
- [x] **Step 2: Remove silent seed fallback in live mode for RFQ list path**
- [x] **Step 3: Update docs to classify mocks as local-only behavior**
- [x] **Step 4: Run unit/build/lint gates**

### Task 3: Phase 3 PR - Awards + Vendor Productionization

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorController.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php` (if binding needed)
- Add/Modify tests under `apps/atomy-q/API/tests/Feature/...`
- Modify WEB hooks/pages depending on vendor payload parity

- [x] **Step 1: Add failing vendor API tests for tenant-scoped real data**
- [x] **Step 2: Replace vendor stubs with adapter-backed persistence/query paths**
- [ ] **Step 3: Add missing award journey closure tests (compare -> award visibility/finalize)**
- [ ] **Step 4: Regenerate OpenAPI/client and verify WEB compatibility**

### Task 4: Phase 4 PR - Identity Surface Hardening

**Files:**
- Modify identity Layer 3 bindings/controllers/services in `apps/atomy-q/API/app/...`
- Add/modify tests in `apps/atomy-q/API/tests/Feature/...`

- [ ] **Step 1: Replace remaining no-op/stub identity surfaces in alpha-required paths**
- [ ] **Step 2: Ensure tenant-safe role/permission/user endpoints (or explicitly gate as out-of-scope)**
- [ ] **Step 3: Add regression tests and verify `IdentityGap7Test` + related suites**

### Task 5: Phase 5 PR - AI Normalization Production Wiring (Post model-decision gate)

**Files:**
- Modify: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/*`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify: API env/runbook docs
- Add/modify feature tests for deterministic CI fakes + live-mode behavior

- [ ] **Step 1: Define model/provider contract and env variables**
- [ ] **Step 2: Replace mock intelligence bindings for live mode**
- [ ] **Step 3: Preserve deterministic CI through test doubles and contract tests**
- [ ] **Step 4: Verify quote-ingestion and comparison workflow tests**

### Task 6: Cross-cutting Documentation Governance

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_REMAINING_GAPS_IMPLEMENTATION_SPEC_2026-04-09.md`
- Modify: `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_GAP_8_9_10_MITIGATION_PLAN_2026-04-09.md`
- Modify: `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-INDEX.md`

- [x] **Step 1: After each phase, update gap status and verification evidence**
- [x] **Step 2: Keep index pointing only to current execution docs**
- [x] **Step 3: Record reopened gaps and closure dates explicitly**
