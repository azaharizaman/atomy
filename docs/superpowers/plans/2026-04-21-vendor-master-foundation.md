# Vendor Master Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the buyer-side vendor master, approval status model, top-level vendor APIs, and WEB vendor workspace required for alpha.

**Architecture:** Introduce a dedicated vendor domain with tenant-scoped persistence and explicit status transitions in Laravel, then expose it through generated API contracts and a top-level `/vendors` UI in Atomy-Q. This plan ends when users can create, review, approve, restrict, suspend, archive, list, and inspect vendors from a top-level navigation surface.

**Tech Stack:** PHP 8.3, Laravel, Eloquent, Next.js/React, TypeScript, TanStack Query, OpenAPI-generated client, PHPUnit, Vitest, Playwright.

---

## File Structure

### Backend / Domain

- Create: `packages/Vendor/composer.json`
- Create: `packages/Vendor/src/Enums/VendorStatus.php`
- Create: `packages/Vendor/src/Contracts/VendorInterface.php`
- Create: `packages/Vendor/src/Contracts/VendorQueryInterface.php`
- Create: `packages/Vendor/src/Contracts/VendorPersistInterface.php`
- Create: `packages/Vendor/src/Contracts/VendorRepositoryInterface.php`
- Create: `packages/Vendor/src/Contracts/VendorStatusTransitionPolicyInterface.php`
- Create: `packages/Vendor/src/ValueObjects/VendorId.php`
- Create: `packages/Vendor/src/ValueObjects/VendorDisplayName.php`
- Create: `packages/Vendor/src/ValueObjects/VendorLegalName.php`
- Create: `packages/Vendor/src/ValueObjects/RegistrationNumber.php`
- Create: `packages/Vendor/src/ValueObjects/VendorApprovalRecord.php`
- Create: `packages/Vendor/src/Exceptions/InvalidVendorStatusTransition.php`
- Create: `packages/Vendor/src/Services/VendorStatusTransitionPolicy.php`

### Laravel Adapter / App

- Create: `adapters/Laravel/Vendor/composer.json`
- Create: `adapters/Laravel/Vendor/src/VendorServiceProvider.php`
- Create: `adapters/Laravel/Vendor/src/Models/EloquentVendor.php`
- Create: `adapters/Laravel/Vendor/src/Repositories/EloquentVendorRepository.php`
- Create: `adapters/Laravel/Vendor/database/migrations/2026_04_21_000001_create_vendors_table.php`
- Create: `app/Http/Controllers/Api/V1/VendorController.php`
- Create: `app/Http/Controllers/Api/V1/VendorStatusController.php`
- Modify: `routes/api.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `openapi/openapi.json`

### Tests

- Create: `packages/Vendor/tests/Unit/Services/VendorStatusTransitionPolicyTest.php`
- Create: `adapters/Laravel/Vendor/tests/Feature/VendorRepositoryTest.php`
- Create: `tests/Feature/Api/V1/VendorApiTest.php`

### WEB

- Create: `apps/atomy-q/WEB/src/hooks/use-vendors.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-vendor.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-create-vendor.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-update-vendor.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-update-vendor-status.ts`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/page.test.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

## Task 1: Scaffold Layer 1 Vendor Domain

**Files:**
- Create: `packages/Vendor/*`
- Test: `packages/Vendor/tests/Unit/Services/VendorStatusTransitionPolicyTest.php`

- [ ] **Step 1: Write failing status-transition tests**

Add tests for allowed transitions:
- `Draft -> UnderReview`
- `UnderReview -> Approved`
- `Approved -> Restricted`
- `Approved -> Suspended`
- `Restricted -> Approved`
- `Suspended -> Approved`
- `Approved -> Archived`

Add tests for rejected transitions:
- `Draft -> Approved`
- `Archived -> Approved`
- `Archived -> Draft`

Run: `cd packages/Vendor && ./vendor/bin/phpunit tests/Unit/Services/VendorStatusTransitionPolicyTest.php`
Expected: FAIL because package files do not exist yet.

- [ ] **Step 2: Create package composer and enum/value objects**

Create the package with:
- `VendorStatus` enum values: `Draft`, `UnderReview`, `Approved`, `Restricted`, `Suspended`, `Archived`
- immutable VO wrappers for IDs and required text fields
- `VendorApprovalRecord` containing `approvedByUserId`, `approvedAt`, `approvalNote`

- [ ] **Step 3: Add contracts and transition policy**

Define:
- `VendorInterface` getters for identity, names, registration, country, status, and approval record
- `VendorQueryInterface` for tenant-scoped list/show lookups
- `VendorPersistInterface` for save and status update operations
- `VendorRepositoryInterface` extending query + persist
- `VendorStatusTransitionPolicyInterface` with `assertCanTransition(from, to)`

Implement `VendorStatusTransitionPolicy` and `InvalidVendorStatusTransition`.

- [ ] **Step 4: Run package tests and fix until green**

Run: `cd packages/Vendor && ./vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 5: Commit domain scaffold**

Run:
```bash
git add packages/Vendor
git commit -m "feat(vendor): add vendor domain foundation"
```

## Task 2: Add Tenant-Scoped Vendor Persistence

**Files:**
- Create: `adapters/Laravel/Vendor/*`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `adapters/Laravel/Vendor/tests/Feature/VendorRepositoryTest.php`

- [ ] **Step 1: Write failing repository tests**

Cover:
- save + fetch by tenant/id
- list filtered by tenant
- cross-tenant lookup returns null
- repository reads rows created by the API's canonical lowercase tenant IDs
- status update persists approval metadata
- repository status update rejects invalid transitions using the domain transition policy

Run: `./vendor/bin/phpunit adapters/Laravel/Vendor/tests/Feature/VendorRepositoryTest.php`
Expected: FAIL because adapter files and migration do not exist.

- [ ] **Step 2: Create migration and model**

Create `vendors` table with at least:
- `id` ULID primary key
- `tenant_id` indexed
- `legal_name`
- `display_name`
- `registration_number`
- `country_of_registration`
- `status`
- `primary_contact_name`
- `primary_contact_email`
- `primary_contact_phone` nullable
- `approved_by_user_id` nullable
- `approved_at` nullable
- `approval_note` nullable text
- timestamps

Add tenant + status + display-name indexes aligned to list queries.

- [ ] **Step 3: Implement repository and bindings**

Implement tenant-scoped methods:
- `findByTenantAndId(string $tenantId, string $vendorId): ?VendorInterface`
- `search(string $tenantId, array $filters = []): array`
- `save(string $tenantId, VendorInterface $vendor): VendorInterface`
- `updateStatus(string $tenantId, string $vendorId, VendorStatus $status, ?VendorApprovalRecord $approvalRecord): VendorInterface`

Bind `VendorRepositoryInterface` to `EloquentVendorRepository`.
Bind `VendorStatusTransitionPolicyInterface` to `VendorStatusTransitionPolicy`.

Repository persistence requirements:
- canonicalize new vendor `tenant_id` writes to lowercase to match API-created rows,
- use case-normalized tenant predicates for reads/updates so existing mixed-case legacy rows remain accessible without cross-tenant leakage,
- enforce `VendorStatusTransitionPolicyInterface` in `updateStatus()` so non-HTTP mutation paths cannot bypass the approved lifecycle.

- [ ] **Step 4: Run repository tests**

Run: `./vendor/bin/phpunit adapters/Laravel/Vendor/tests/Feature/VendorRepositoryTest.php`
Expected: PASS.

- [ ] **Step 5: Commit persistence layer**

Run:
```bash
git add adapters/Laravel/Vendor app/Providers/AppServiceProvider.php
git commit -m "feat(vendor): add tenant-scoped vendor persistence"
```

## Task 3: Expose Vendor CRUD and Status APIs

**Files:**
- Create: `app/Http/Controllers/Api/V1/VendorController.php`
- Create: `app/Http/Controllers/Api/V1/VendorStatusController.php`
- Modify: `routes/api.php`
- Modify: `openapi/openapi.json`
- Test: `tests/Feature/Api/V1/VendorApiTest.php`

- [ ] **Step 1: Write failing API feature tests**

Cover:
- list vendors for current tenant
- create draft vendor
- show vendor for current tenant
- patch vendor core fields
- approve vendor
- reject selection of cross-tenant vendor with 404 semantics
- reject invalid status transition with 422

Run: `./vendor/bin/phpunit tests/Feature/Api/V1/VendorApiTest.php`
Expected: FAIL because routes/controllers do not exist.

- [ ] **Step 2: Implement controller request validation**

Validation rules:
- `legal_name`: required string
- `display_name`: required string
- `registration_number`: required string
- `country_of_registration`: required string
- `primary_contact_name`: required string
- `primary_contact_email`: required email
- `primary_contact_phone`: nullable string
- status update action body includes `status`; `approval_note` required when moving to `Approved`

- [ ] **Step 3: Implement tenant-scoped controller actions**

Expose:
- `GET /vendors`
- `POST /vendors`
- `GET /vendors/{id}`
- `PATCH /vendors/{id}`
- `PATCH /vendors/{id}/status`

Return 404 for cross-tenant access.
Map invalid transitions to 422 with domain message.

- [ ] **Step 4: Regenerate WEB client and rerun tests**

Run:
```bash
cd apps/atomy-q/WEB && npm run generate:api
cd /home/azaharizaman/dev/atomy && ./vendor/bin/phpunit tests/Feature/Api/V1/VendorApiTest.php
```
Expected: API tests PASS and generated client contains vendor create/update/status operations.

- [ ] **Step 5: Commit API slice**

Run:
```bash
git add app/Http/Controllers/Api/V1 routes/api.php openapi/openapi.json apps/atomy-q/WEB/src/generated/api
git commit -m "feat(vendor): add vendor master api"
```

## Task 4: Add Top-Level Vendors WEB Workspace

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-vendors.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-vendor.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-create-vendor.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-update-vendor.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-update-vendor-status.ts`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/page.test.tsx`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.test.tsx`

- [ ] **Step 1: Write failing WEB tests**

Cover list page states:
- loading
- error
- empty
- populated rows with status badges

Cover detail page states:
- overview rendering
- approval metadata visible for approved vendor
- status action error surfaced
- edit core vendor fields and submit through `use-update-vendor`

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/vendors/page.test.tsx src/app/'(dashboard)'/vendors/[vendorId]/page.test.tsx
```
Expected: FAIL because pages/hooks do not exist.

- [ ] **Step 2: Add vendor hooks using generated client**

Implement hooks that:
- fail loud on malformed live payloads
- never cross-fallback from live to mock when `NEXT_PUBLIC_USE_MOCKS=false`
- normalize vendor status and approval record fields

- [ ] **Step 3: Add top-level navigation and pages**

Update `MAIN_NAV_ITEMS` to include `Vendors` with path `/vendors`.
Build `/vendors` list with filters for status + text query and a create-vendor action.
Build `/vendors/[vendorId]` detail with overview, edit form for core vendor fields, and status transition controls.

- [ ] **Step 4: Run WEB tests and lint**

Run:
```bash
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/vendors/page.test.tsx src/app/'(dashboard)'/vendors/[vendorId]/page.test.tsx
cd apps/atomy-q/WEB && npm run lint
```
Expected: tests PASS; lint passes with only pre-existing warnings outside this slice.

- [ ] **Step 5: Commit WEB vendor workspace**

Run:
```bash
git add apps/atomy-q/WEB/src/config/nav.ts apps/atomy-q/WEB/src/hooks apps/atomy-q/WEB/src/app/'(dashboard)'/vendors
git commit -m "feat(atomy-q): add vendor master workspace"
```

## Task 5: Documentation and Verification

**Files:**
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `adapters/Laravel/Vendor/IMPLEMENTATION_SUMMARY.md` if created during implementation

- [ ] **Step 1: Update implementation summaries**

Document:
- top-level Vendors navigation
- vendor master CRUD/status APIs
- approved status model and tenant-scoped behavior

- [ ] **Step 2: Run end-to-end verification commands**

Run:
```bash
./vendor/bin/phpunit tests/Feature/Api/V1/VendorApiTest.php adapters/Laravel/Vendor/tests/Feature/VendorRepositoryTest.php
cd apps/atomy-q/WEB && npx vitest run src/app/'(dashboard)'/vendors/page.test.tsx src/app/'(dashboard)'/vendors/[vendorId]/page.test.tsx
cd apps/atomy-q/WEB && npm run build
```
Expected: PASS.

- [ ] **Step 3: Commit docs and verification updates**

Run:
```bash
git add apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md adapters/Laravel/Vendor/IMPLEMENTATION_SUMMARY.md
git commit -m "docs(vendor): record vendor master foundation"
```
