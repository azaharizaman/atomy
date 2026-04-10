# Alpha Progress Gap 6 Tenant / Company Lifecycle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a minimal, first-class company onboarding flow for Atomy-Q Alpha that creates a real tenant row, creates the first owner user in the same transaction, and bootstraps the authenticated session, while keeping the richer tenant admin lifecycle documented for later.

**Architecture:** Keep `Nexus\Tenant` as the source of truth for tenant lifecycle rules and validation, use a small Layer 2 onboarding coordinator to compose tenant creation with owner-user creation, and let the Laravel API own the HTTP endpoint, transaction, migrations, and JWT bootstrap. The WEB app should only render the onboarding form and call the public onboarding endpoint. Post-alpha tenant administration stays in docs and backlog notes so the alpha path remains narrow and honest.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit, Next.js 16, React, Vitest, Nexus `Tenant`, Nexus `Identity`, Nexus `TenantOperations`, Nexus `IdentityOperations`, Scramble/OpenAPI.

---

## File Structure

### Layer 1: `packages/Tenant`

**Create**
- `packages/Tenant/tests/Unit/Services/TenantLifecycleServiceTest.php` - cover onboarding validation and lifecycle defaults if package tests are missing.

**Modify**
- `packages/Tenant/src/Contracts/TenantValidationInterface.php` - add company name uniqueness validation if the alpha onboarding flow needs it.
- `packages/Tenant/src/Services/TenantLifecycleService.php` - enforce the new validation rule during create/update flows.
- `packages/Tenant/IMPLEMENTATION_SUMMARY.md` - document the tenant lifecycle extension and the alpha onboarding use case.

### Layer 2: `orchestrators/TenantOperations` and `orchestrators/IdentityOperations`

**Create**
- `orchestrators/TenantOperations/src/Contracts/TenantCompanyOnboardingCoordinatorInterface.php` - compact alpha-facing onboarding contract.
- `orchestrators/TenantOperations/src/DTOs/TenantCompanyOnboardingRequest.php` - input contract for company creation.
- `orchestrators/TenantOperations/src/DTOs/TenantCompanyOnboardingResult.php` - output contract for tenant + owner + bootstrap data.
- `orchestrators/TenantOperations/tests/Unit/Coordinators/TenantCompanyOnboardingCoordinatorTest.php` - cover atomic onboarding success and failure paths.

**Modify**
- `orchestrators/TenantOperations/src/Services/TenantOnboardingService.php` or a new focused alpha onboarding service - compose tenant creation and owner creation without pulling HTTP concerns into Layer 2.
- `orchestrators/TenantOperations/IMPLEMENTATION_SUMMARY.md` - create this summary file and document the new minimal onboarding boundary and the deferred admin lifecycle.
- `orchestrators/IdentityOperations/src/Contracts/UserOnboardingCoordinatorInterface.php` - only if a smaller owner-user creation shape is required by the onboarding service.
- `orchestrators/IdentityOperations/IMPLEMENTATION_SUMMARY.md` - note how user creation is consumed by tenant onboarding.

**Test**
- `orchestrators/IdentityOperations/tests/Unit/Coordinators/UserOnboardingCoordinatorTest.php` - cover owner-user creation contract if the interface changes.

### Layer 3: `apps/atomy-q/API`

**Create**
- `apps/atomy-q/API/database/migrations/2026_04_07_000001_create_tenants_table.php` - add the real tenant table if the current migration set still lacks it.
- `apps/atomy-q/API/app/Http/Requests/RegisterCompanyRequest.php` - validate onboarding input.
- `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantPersistence.php` - persist tenant rows via Eloquent.
- `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantValidation.php` - implement tenant uniqueness checks for the package.
- `apps/atomy-q/API/tests/Feature/Api/RegisterCompanyTest.php` - cover success, validation, duplicate company, and rollback cases.

**Modify**
- `apps/atomy-q/API/routes/api.php` - add the public onboarding route under `auth`.
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php` or a new onboarding controller - call the Layer 2 coordinator and issue JWTs on success.
- `apps/atomy-q/API/app/Models/Tenant.php` - align the model with the real tenant table and package contract.
- `apps/atomy-q/API/app/Models/User.php` - ensure the owner user relation and tenant fields match onboarding output.
- `apps/atomy-q/API/app/Adapters/Tenant/...` - bind tenant persistence / validation adapters for `Nexus\Tenant`.
- `apps/atomy-q/API/app/Providers/AppServiceProvider.php` - bind the new onboarding contracts and tenant adapters.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` - document the new onboarding path and boundaries.
- `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md` - mark gap 6 as addressed and point future tenant admin work at the backlog note.

### Layer 3: `apps/atomy-q/WEB`

**Create**
- `apps/atomy-q/WEB/src/app/(auth)/register-company/page.tsx` - minimal onboarding UI for Alpha.
- `apps/atomy-q/WEB/src/app/(auth)/register-company/page.test.tsx` - cover validation, submit, and redirect behavior.

**Modify**
- `apps/atomy-q/WEB/src/app/(auth)/login/page.tsx` - add a link to the onboarding screen if it does not already exist.
- `apps/atomy-q/WEB/src/hooks/use-auth.ts` or the auth store - add a helper for storing the onboarding-issued tokens if needed.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` - document the onboarding path.
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md` - close the tenant/company lifecycle gap and list the deferred admin features.

---

## Task 1: Tighten the tenant package for onboarding validation

**Files:**
- Modify: `packages/Tenant/src/Contracts/TenantValidationInterface.php`
- Modify: `packages/Tenant/src/Services/TenantLifecycleService.php`
- Test: `packages/Tenant/tests/...`
- Modify: `packages/Tenant/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Write the failing package test**

  Add a unit test that proves tenant onboarding rejects duplicate company names if the alpha flow depends on that rule, and that successful creation still uses the package lifecycle defaults.

- [ ] **Step 2: Run the tenant package test to confirm the baseline fails**

  Run:
  ```bash
  cd packages/Tenant
  ./vendor/bin/phpunit tests/Unit/Services/TenantLifecycleServiceTest.php
  ```

  Expected: FAIL because the name uniqueness contract is not yet wired into the package lifecycle.

- [ ] **Step 3: Add the minimal package change**

  Add `nameExists(string $name, ?string $excludeId = null): bool` to the validation contract if the onboarding spec keeps company names unique, then make `TenantLifecycleService::createTenant()` check it before persistence.

- [ ] **Step 4: Re-run the package tests**

  Run:
  ```bash
  cd packages/Tenant
  ./vendor/bin/phpunit tests/Unit/Services/TenantLifecycleServiceTest.php
  ```

  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add packages/Tenant/src/Contracts/TenantValidationInterface.php \
          packages/Tenant/src/Services/TenantLifecycleService.php \
          packages/Tenant/IMPLEMENTATION_SUMMARY.md \
          packages/Tenant/tests/Unit/Services/TenantLifecycleServiceTest.php
  git commit -m "feat(tenant): support onboarding validation"
  ```

---

## Task 2: Add the onboarding coordinator boundary in Layer 2

**Files:**
- Create: `orchestrators/TenantOperations/src/Contracts/TenantCompanyOnboardingCoordinatorInterface.php`
- Create: `orchestrators/TenantOperations/src/DTOs/TenantCompanyOnboardingRequest.php`
- Create: `orchestrators/TenantOperations/src/DTOs/TenantCompanyOnboardingResult.php`
- Modify: `orchestrators/TenantOperations/src/Services/TenantOnboardingService.php` or add a focused onboarding service
- Modify: `orchestrators/TenantOperations/IMPLEMENTATION_SUMMARY.md`
- Test: `orchestrators/TenantOperations/tests/...`

- [ ] **Step 1: Write the failing coordinator test**

  Add a unit test that proves tenant creation and owner user creation are treated as one atomic onboarding workflow and that failures short-circuit without returning partial success.

- [ ] **Step 2: Run the orchestrator test to confirm the baseline fails**

  Run:
  ```bash
  ./vendor/bin/phpunit tests/Unit/Coordinators/TenantCompanyOnboardingCoordinatorTest.php
  ```
  from `orchestrators/TenantOperations`.

  Expected: FAIL because the alpha-facing onboarding contract/result does not exist yet.

- [ ] **Step 3: Implement the minimal orchestration contract**

  Add a compact onboarding request/result pair and a coordinator method that:
  - generates or validates the tenant code
  - creates the tenant through `Nexus\Tenant`
  - creates the first owner user through the Identity onboarding boundary
  - returns the created tenant ID, owner user ID, and any session bootstrap data needed by the API

- [ ] **Step 4: Re-run the orchestrator tests**

  Run:
  ```bash
  ./vendor/bin/phpunit tests/Unit/Coordinators/TenantCompanyOnboardingCoordinatorTest.php
  ```
  from `orchestrators/TenantOperations`.

  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add orchestrators/TenantOperations/src/Contracts/TenantCompanyOnboardingCoordinatorInterface.php \
          orchestrators/TenantOperations/src/DTOs/TenantCompanyOnboardingRequest.php \
          orchestrators/TenantOperations/src/DTOs/TenantCompanyOnboardingResult.php \
          orchestrators/TenantOperations/src/Services/TenantOnboardingService.php \
          orchestrators/TenantOperations/IMPLEMENTATION_SUMMARY.md \
          orchestrators/TenantOperations/tests/Unit/Coordinators/TenantCompanyOnboardingCoordinatorTest.php
  git commit -m "feat(onboarding): add tenant company workflow"
  ```

---

## Task 3: Wire the Laravel API and tenant persistence

**Files:**
- Create: `apps/atomy-q/API/database/migrations/*_create_tenants_table.php`
- Create: `apps/atomy-q/API/app/Http/Requests/RegisterCompanyRequest.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php` or add a dedicated onboarding controller
- Modify: `apps/atomy-q/API/app/Models/Tenant.php`
- Modify: `apps/atomy-q/API/app/Models/User.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Create: `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantPersistence.php` and `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantValidation.php` if the adapter pair does not already exist in the app tree.
- Test: `apps/atomy-q/API/tests/Feature/Api/RegisterCompanyTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`

- [ ] **Step 1: Write the failing API feature tests**

  Cover:
  - successful company onboarding returns JWT tokens and creates both tenant and owner user
  - duplicate company name or code returns 422
  - invalid payload returns 422
  - a forced owner-user creation failure rolls back the tenant insert
  - the response shape is compatible with the existing auth bootstrap used by login

- [ ] **Step 2: Run the API tests to confirm the baseline fails**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/Api/RegisterCompanyTest.php
  ```

  Expected: FAIL because the route, request, adapter, and migration are not in place yet.

- [ ] **Step 3: Implement the Laravel adapter layer**

  Add the tenants migration, update the `Tenant` model to match the real table and package contract, bind the `Nexus\Tenant` persistence/validation adapters, and wire the onboarding controller to call the Layer 2 coordinator inside a DB transaction.

- [ ] **Step 4: Re-run the API tests**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/Api/RegisterCompanyTest.php
  ```

  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/API/database/migrations/2026_04_07_000001_create_tenants_table.php \
          apps/atomy-q/API/app/Http/Requests/RegisterCompanyRequest.php \
          apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantPersistence.php \
          apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantValidation.php \
          apps/atomy-q/API/routes/api.php \
          apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php \
          apps/atomy-q/API/app/Models/Tenant.php \
          apps/atomy-q/API/app/Models/User.php \
          apps/atomy-q/API/app/Providers/AppServiceProvider.php \
          apps/atomy-q/API/tests/Feature/Api/RegisterCompanyTest.php \
          apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
          apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md
  git commit -m "feat(api): add company onboarding"
  ```

---

## Task 4: Build the minimal WEB onboarding screen

**Files:**
- Create: `apps/atomy-q/WEB/src/app/(auth)/register-company/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(auth)/register-company/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(auth)/login/page.tsx`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/BACKEND_API_GAPS.md`

- [ ] **Step 1: Write the failing WEB test**

  Add a page test that proves the onboarding form posts the company/owner payload, renders validation errors, and redirects after the auth response is stored.

- [ ] **Step 2: Run the WEB test to confirm the baseline fails**

  Run:
  ```bash
  cd apps/atomy-q/WEB
  npx vitest run src/app/'(auth)'/register-company/page.test.tsx
  ```

  Expected: FAIL because the page does not exist yet.

- [ ] **Step 3: Implement the onboarding page**

  Build a minimal auth-page form with:
  - company name
  - owner name
  - owner email
  - password
  - optional timezone/locale/currency defaults

  Submit to the new onboarding endpoint, store the returned tokens, and redirect into the dashboard.

- [ ] **Step 4: Re-run the WEB test**

  Run:
  ```bash
  cd apps/atomy-q/WEB
  npx vitest run src/app/'(auth)'/register-company/page.test.tsx
  ```

  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/WEB/src/app/'(auth)'/register-company/page.tsx \
          apps/atomy-q/WEB/src/app/'(auth)'/register-company/page.test.tsx \
          apps/atomy-q/WEB/src/app/'(auth)'/login/page.tsx \
          apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
          apps/atomy-q/WEB/BACKEND_API_GAPS.md
  git commit -m "feat(web): add company onboarding"
  ```

---

## Task 5: Sync docs and preserve the deferred backlog

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- Modify: `orchestrators/TenantOperations/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Update the progress and implementation docs**

  Mark alpha gap 6 as closed in the progress analysis and add a dedicated post-alpha tenant lifecycle backlog note that preserves the deferred items:
  - rename
  - deactivate/reactivate
  - archive/delete
  - invite admins
  - multi-tenant switching
  - custom domains/subdomains
  - hierarchy management

- [ ] **Step 2: Run the final targeted verification**

  Run the package, API, and WEB tests touched by the onboarding flow.

- [ ] **Step 3: Commit**

  ```bash
  git add apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md \
          apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
          apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
          apps/atomy-q/WEB/BACKEND_API_GAPS.md \
          orchestrators/TenantOperations/IMPLEMENTATION_SUMMARY.md
  git commit -m "docs: close alpha tenant lifecycle gap"
  ```
