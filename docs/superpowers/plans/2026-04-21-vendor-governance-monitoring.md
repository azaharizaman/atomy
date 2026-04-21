# Vendor Governance Monitoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add ESG, compliance, and risk evidence recording plus health scores and warning surfaces for vendors without turning those signals into automatic alpha eligibility gates.

**Architecture:** Introduce separate vendor governance records for evidence and findings, compute summarized read models and scores, then surface those warnings in vendor detail and requisition selection contexts. Manual vendor status remains the only eligibility gate for alpha.

**Tech Stack:** PHP 8.3, Laravel, Eloquent, ESGOperations/ProcurementOperations integration, React/TypeScript, PHPUnit, Vitest.

---

## File Structure

- Create: `adapters/Laravel/Vendor/database/migrations/2026_04_21_000003_create_vendor_governance_tables.php`
- Create: `app/Models/VendorEvidence.php`
- Create: `app/Models/VendorFinding.php`
- Create: `app/Services/VendorGovernanceScoreService.php`
- Create: `app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- Modify: `routes/api.php`
- Modify: `openapi/openapi.json`
- Create: `tests/Feature/Api/V1/VendorGovernanceApiTest.php`
- Create: `tests/Unit/Services/VendorGovernanceScoreServiceTest.php`
- Create: `apps/atomy-q/WEB/src/hooks/use-vendor-governance.ts`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`

## Task 1: Add Governance Persistence Model

- [ ] **Step 1: Write failing score-service and API tests**

Cover:
- evidence can be listed per vendor
- findings can be listed per vendor
- scores summarize freshness/open-severity correctly
- cross-tenant access returns 404

Run:
```bash
./vendor/bin/phpunit tests/Unit/Services/VendorGovernanceScoreServiceTest.php tests/Feature/Api/V1/VendorGovernanceApiTest.php
```
Expected: FAIL.

- [ ] **Step 2: Add governance tables**

Create tables:
- `vendor_evidence`
- `vendor_findings`

`vendor_evidence` fields:
- `tenant_id`, `vendor_id`, `domain`, `type`, `title`, `source`, `observed_at`, `expires_at`, `review_status`, `reviewed_by`, `notes`

`vendor_findings` fields:
- `tenant_id`, `vendor_id`, `domain`, `issue_type`, `severity`, `status`, `opened_at`, `opened_by`, `remediation_owner`, `remediation_due_at`, `resolution_summary`

- [ ] **Step 3: Implement score service**

Compute:
- `esg_score`
- `compliance_health_score`
- `risk_watch_score`
- `evidence_freshness_score`

Do not mutate vendor status from these values.
Return warning flags separately.

- [ ] **Step 4: Run tests**

Run:
```bash
./vendor/bin/phpunit tests/Unit/Services/VendorGovernanceScoreServiceTest.php tests/Feature/Api/V1/VendorGovernanceApiTest.php
```
Expected: PASS.

- [ ] **Step 5: Commit persistence and scoring**

Run:
```bash
git add app/Models/VendorEvidence.php app/Models/VendorFinding.php app/Services/VendorGovernanceScoreService.php tests/Unit/Services/VendorGovernanceScoreServiceTest.php tests/Feature/Api/V1/VendorGovernanceApiTest.php
 git commit -m "feat(vendor): add governance evidence and scoring"
```

## Task 2: Expose Governance APIs

- [ ] **Step 1: Add controller and routes**

Expose at least:
- `GET /vendors/{id}/governance`
- `GET /vendors/{id}/due-diligence`
- `PATCH /vendors/{id}/due-diligence/{itemId}` if current openapi contract already expects this shape
- `POST /vendors/{id}/sanctions-screening` if a stub/manual record path already exists

Return:
- evidence list
- findings list
- summary scores
- warning flags

- [ ] **Step 2: Regenerate WEB client**

Run:
```bash
cd apps/atomy-q/WEB && npm run generate:api
```
Expected: generated client updated with governance endpoints.

- [ ] **Step 3: Re-run API tests**

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/VendorGovernanceApiTest.php`
Expected: PASS.

- [ ] **Step 4: Commit API layer**

Run:
```bash
git add app/Http/Controllers/Api/V1/VendorGovernanceController.php routes/api.php openapi/openapi.json apps/atomy-q/WEB/src/generated/api
 git commit -m "feat(vendor): expose vendor governance api"
```

## Task 3: Add WEB Governance Surfaces

- [ ] **Step 1: Write failing WEB tests**

Cover:
- governance page renders scores
- warning badges visible on vendor detail
- stale evidence warning visible in requisition vendor panel
- unavailable live payload surfaces explicit error state

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/vendors/[vendorId]/esg-compliance/page.test.tsx
```
Expected: FAIL.

- [ ] **Step 2: Implement governance hook and pages**

Create `use-vendor-governance.ts` to normalize:
- evidence
- findings
- score summary
- warning flags

Add `/vendors/[vendorId]/esg-compliance` page.
Add warning summary chips to vendor detail overview.
Add non-blocking warnings to requisition vendor selection panel.

- [ ] **Step 3: Run WEB tests**

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/vendors/[vendorId]/esg-compliance/page.test.tsx
```
Expected: PASS.

- [ ] **Step 4: Commit WEB governance UX**

Run:
```bash
git add apps/atomy-q/WEB/src/hooks/use-vendor-governance.ts apps/atomy-q/WEB/src/app/'(dashboard)'/vendors apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs
 git commit -m "feat(atomy-q): surface vendor governance warnings"
```

## Task 4: Verification And Documentation

- [ ] **Step 1: Update implementation summaries**

Document:
- evidence registry
- finding model
- score behavior
- warning-not-blocking rule for alpha

- [ ] **Step 2: Run verification commands**

Run:
```bash
./vendor/bin/phpunit tests/Unit/Services/VendorGovernanceScoreServiceTest.php tests/Feature/Api/V1/VendorGovernanceApiTest.php
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/vendors/[vendorId]/esg-compliance/page.test.tsx
```
Expected: PASS.

- [ ] **Step 3: Commit docs**

Run:
```bash
git add apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
 git commit -m "docs(vendor): record governance monitoring"
```
