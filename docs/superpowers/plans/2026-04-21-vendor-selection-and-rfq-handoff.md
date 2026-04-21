# Vendor Selection And RFQ Handoff Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce approved-vendor-only requisition selection and make RFQ invitations derive from that approved shortlist.

**Architecture:** Add selected-vendor persistence and APIs at the requisition level, then update RFQ creation/invitation flows so vendor roster and invitation operations are constrained by requisition-approved vendor selections. This plan closes the current loophole where RFQ vendor activity can exist without a vendor-master-first workflow.

**Tech Stack:** PHP 8.3, Laravel, Eloquent, Next.js/React, TypeScript, TanStack Query, PHPUnit, Vitest, Playwright.

---

## File Structure

- Create: `adapters/Laravel/Vendor/database/migrations/2026_04_21_000002_create_requisition_selected_vendors_table.php`
- Create: `app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php`
- Modify: `routes/api.php`
- Modify: `openapi/openapi.json`
- Modify: `app/Http/Controllers/Api/V1/RfqInvitationController.php` or equivalent invitation write controller
- Create: `tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`
- Modify: `tests/Feature/Api/V1/RfqInvitationApiTest.php` or create if missing
- Create: `apps/atomy-q/WEB/src/hooks/use-requisition-vendor-selection.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-update-requisition-vendor-selection.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/new/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.tsx` or requisition-edit surface if that is where selection belongs
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx`
- Modify: `apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts`

## Task 1: Persist Selected Vendors On Requisition

**Files:**
- Create migration + controller + tests listed above

- [ ] **Step 1: Write failing API tests for selected-vendor persistence**

Cover:
- save selected vendors for requisition
- list selected vendors for requisition
- reject vendor IDs not in tenant
- reject vendor IDs not in `Approved` status
- reject duplicate vendor IDs

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`
Expected: FAIL because table/route/controller do not exist.

- [ ] **Step 2: Add selected-vendor join table**

Create `requisition_selected_vendors` with:
- `id`
- `tenant_id`
- `rfq_id` or requisition ID aligned to current Atomy-Q RFQ/requisition model
- `vendor_id`
- `selected_by_user_id`
- timestamps

Add unique index on `(tenant_id, rfq_id, vendor_id)`.

- [ ] **Step 3: Implement controller and validation**

Expose:
- `GET /rfqs/{rfqId}/selected-vendors`
- `PUT /rfqs/{rfqId}/selected-vendors`

Validation:
- body requires non-empty array of vendor IDs
- `distinct`
- every vendor must be same-tenant and `Approved`

- [ ] **Step 4: Run API tests**

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`
Expected: PASS.

- [ ] **Step 5: Commit selected-vendor persistence**

Run:
```bash
git add app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php routes/api.php openapi/openapi.json tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php
git commit -m "feat(sourcing): persist approved requisition vendor selection"
```

## Task 2: Constrain RFQ Invitations To Selected Vendors

**Files:**
- Modify invitation write controller/tests

- [ ] **Step 1: Write failing invitation guard tests**

Cover:
- invite succeeds when vendor is in requisition selected list
- invite fails when vendor is approved but not selected on requisition
- remind still works only for existing invitation within tenant

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/RfqInvitationApiTest.php`
Expected: FAIL on new guard cases.

- [ ] **Step 2: Add selected-vendor guard to invitation create path**

Before creating invitation:
- confirm RFQ belongs to tenant
- confirm vendor belongs to tenant and is approved
- confirm vendor is selected on requisition/RFQ
- reject with 422 if not selected

- [ ] **Step 3: Keep RFQ invitation roster read model aligned**

Ensure `GET /rfqs/{rfqId}/invitations` continues to return roster rows with:
- invitation id
- vendor id
- vendor name
- email/contact
- status

No synthetic rows for uninvited vendors.

- [ ] **Step 4: Run invitation tests**

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/RfqInvitationApiTest.php`
Expected: PASS.

- [ ] **Step 5: Commit invitation guard**

Run:
```bash
git add app/Http/Controllers/Api/V1/RfqInvitationController.php tests/Feature/Api/V1/RfqInvitationApiTest.php
git commit -m "feat(sourcing): constrain rfq invitations to selected vendors"
```

## Task 3: Add WEB Vendor Selection Panel

**Files:**
- Create hooks and test
- Modify requisition create/edit and RFQ vendors page

- [ ] **Step 1: Write failing WEB tests**

Cover:
- panel lists approved vendors only
- save action posts selected IDs
- non-approved vendors never appear
- empty-state copy directs user to vendor master

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx
```
Expected: FAIL because hook/component do not exist.

- [ ] **Step 2: Implement hooks for get/update selected vendors**

Hooks should:
- read vendor selection for RFQ/requisition
- update selected vendor IDs atomically
- invalidate invitation roster and requisition detail queries after save

- [ ] **Step 3: Build selection UX**

In requisition workflow:
- search approved vendors
- show selected chips/list
- save selected vendors
- block inline vendor creation
- link to `/vendors` when no suitable vendor exists

In RFQ vendors page:
- show selected vendors and invitation state distinctly
- disable invite button until selected vendors exist

- [ ] **Step 4: Run WEB tests and alpha journey tests**

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx
cd apps/atomy-q/WEB && npx playwright test tests/rfq-alpha-journeys.spec.ts --grep "vendors"
```
Expected: PASS.

- [ ] **Step 5: Commit WEB selection flow**

Run:
```bash
git add apps/atomy-q/WEB/src/hooks apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts
git commit -m "feat(atomy-q): add approved vendor selection to requisitions"
```

## Task 4: Documentation And Verification

- [ ] **Step 1: Update implementation summaries**

Document:
- approved-only requisition selection
- RFQ invitation guard behavior
- no inline vendor creation in requisition flow

- [ ] **Step 2: Run full verification for this slice**

Run:
```bash
./vendor/bin/phpunit tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php tests/Feature/Api/V1/RfqInvitationApiTest.php
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx
cd apps/atomy-q/WEB && npx playwright test tests/rfq-alpha-journeys.spec.ts
```
Expected: PASS.

- [ ] **Step 3: Commit docs**

Run:
```bash
git add apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
git commit -m "docs(sourcing): record approved vendor selection workflow"
```
