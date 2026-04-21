# Vendor Recommendation Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a hybrid vendor recommendation engine that pre-fills a draft shortlist for requisitions using deterministic scoring plus bounded LLM enrichment.

**Architecture:** Build deterministic candidate scoring first and expose its explainable output through a stable contract. Then add an optional LLM enrichment layer that improves rationale and bounded ranking without ever bypassing approved-vendor eligibility. The WEB app consumes the recommendation result to prefill the selection panel while keeping the user in final control.

**Tech Stack:** PHP 8.3, Laravel, orchestrators, React/TypeScript, TanStack Query, optional LLM adapter, PHPUnit, Vitest.

---

## File Structure

- Create: `orchestrators/ProcurementOperations/src/DTOs/VendorRecommendation/VendorRecommendationRequest.php`
- Create: `orchestrators/ProcurementOperations/src/DTOs/VendorRecommendation/VendorRecommendationCandidate.php`
- Create: `orchestrators/ProcurementOperations/src/DTOs/VendorRecommendation/VendorRecommendationResult.php`
- Create: `orchestrators/ProcurementOperations/src/Contracts/VendorRecommendationCoordinatorInterface.php`
- Create: `orchestrators/ProcurementOperations/src/Contracts/VendorRecommendationLlmInterface.php`
- Create: `orchestrators/ProcurementOperations/src/Coordinators/VendorRecommendationCoordinator.php`
- Create: `orchestrators/ProcurementOperations/src/Services/DeterministicVendorScorer.php`
- Create: `orchestrators/ProcurementOperations/src/Services/NullVendorRecommendationLlm.php`
- Create: `orchestrators/ProcurementOperations/tests/Unit/Services/DeterministicVendorScorerTest.php`
- Create: `orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php`
- Create: `app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify: `routes/api.php`
- Modify: `openapi/openapi.json`
- Create: `tests/Feature/Api/V1/VendorRecommendationApiTest.php`
- Create: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/new/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`

## Task 1: Deterministic Scoring Core

- [ ] **Step 1: Write failing scorer tests**

Cover scoring for:
- category overlap increases score
- geography mismatch penalizes score
- recent activity boosts score
- non-approved vendor excluded before scoring
- explanations include deterministic reasons

Run:
```bash
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Services/DeterministicVendorScorerTest.php
```
Expected: FAIL because scorer classes do not exist.

- [ ] **Step 2: Implement request/result DTOs and scorer**

Deterministic input should include:
- requisition category/taxonomy
- free-text description
- geography
- spend band
- selected line-item summary
- approved vendor candidate records

Deterministic output should include:
- candidate vendor id/name
- fit score
- deterministic reasons
- warnings
- excluded reasons for rejected candidates

- [ ] **Step 3: Run scorer tests**

Run: `./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Services/DeterministicVendorScorerTest.php`
Expected: PASS.

- [ ] **Step 4: Commit deterministic scorer**

Run:
```bash
git add orchestrators/ProcurementOperations/src/DTOs/VendorRecommendation orchestrators/ProcurementOperations/src/Services/DeterministicVendorScorer.php
 git commit -m "feat(sourcing): add deterministic vendor scoring"
```

## Task 2: Add Bounded LLM Enrichment Coordinator

- [ ] **Step 1: Write failing coordinator tests**

Cover:
- coordinator returns deterministic results when no LLM available
- LLM may enrich explanation
- LLM may adjust score only within bounded delta
- ineligible vendors remain excluded regardless of LLM output

Run:
```bash
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php
```
Expected: FAIL.

- [ ] **Step 2: Implement coordinator and null LLM adapter**

Create:
- `VendorRecommendationCoordinator`
- `VendorRecommendationLlmInterface`
- `NullVendorRecommendationLlm`

Bound score adjustment to a small fixed window, for example `+/- 10` points, after deterministic scoring.
Reject any LLM output that attempts to introduce unknown or ineligible vendors.

- [ ] **Step 3: Run coordinator tests**

Run: `./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php`
Expected: PASS.

- [ ] **Step 4: Commit coordinator**

Run:
```bash
git add orchestrators/ProcurementOperations/src/Contracts/VendorRecommendation* orchestrators/ProcurementOperations/src/Coordinators/VendorRecommendationCoordinator.php
 git commit -m "feat(sourcing): add bounded vendor recommendation coordinator"
```

## Task 3: Expose Recommendation API

- [ ] **Step 1: Write failing API feature tests**

Cover:
- recommendations return approved vendors only
- response contains fit score, reasons, warnings
- malformed request returns 422
- cross-tenant requisition access returns 404

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/VendorRecommendationApiTest.php`
Expected: FAIL.

- [ ] **Step 2: Add controller and route**

Expose:
- `POST /rfqs/{rfqId}/vendor-recommendations`

Request body should accept structured requisition context if not derivable entirely from existing record.
Response should include ranked candidates and excluded reasons.

- [ ] **Step 3: Regenerate WEB client and run API tests**

Run:
```bash
cd apps/atomy-q/WEB && npm run generate:api
cd /home/azaharizaman/dev/atomy && ./vendor/bin/phpunit tests/Feature/Api/V1/VendorRecommendationApiTest.php
```
Expected: PASS.

- [ ] **Step 4: Commit API**

Run:
```bash
git add app/Http/Controllers/Api/V1/VendorRecommendationController.php routes/api.php openapi/openapi.json apps/atomy-q/WEB/src/generated/api
 git commit -m "feat(sourcing): add vendor recommendation api"
```

## Task 4: Prefill WEB Selection Panel With Recommendations

- [ ] **Step 1: Write failing WEB tests**

Cover:
- recommendations auto-populate draft shortlist
- user can remove recommended vendor
- user can add another approved vendor not in recommendation set
- explanation drawer shows reasons and warnings

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx
```
Expected: FAIL.

- [ ] **Step 2: Implement `use-vendor-recommendations.ts` and UI integration**

Add hook to call recommendation API and normalize result.
Update selection panel to:
- load recommendations on demand or when requisition context is complete,
- prefill candidate list,
- mark entries as `Recommended`,
- allow user override before save.

- [ ] **Step 3: Run WEB tests**

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx
```
Expected: PASS.

- [ ] **Step 4: Commit WEB recommendation UX**

Run:
```bash
git add apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs
 git commit -m "feat(atomy-q): prefill vendor shortlist with recommendations"
```

## Task 5: Verification And Documentation

- [ ] **Step 1: Update implementation summaries**

Document:
- deterministic scoring inputs
- bounded LLM role
- user-confirmed shortlist behavior

- [ ] **Step 2: Run verification commands**

Run:
```bash
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Services/DeterministicVendorScorerTest.php orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php tests/Feature/Api/V1/VendorRecommendationApiTest.php
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx
```
Expected: PASS.

- [ ] **Step 3: Commit docs**

Run:
```bash
git add apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md orchestrators/ProcurementOperations/IMPLEMENTATION_SUMMARY.md
 git commit -m "docs(sourcing): record vendor recommendation workflow"
```
