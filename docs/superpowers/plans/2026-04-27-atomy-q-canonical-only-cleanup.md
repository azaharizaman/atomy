# Atomy-Q Canonical-Only Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove compatibility shims, old field aliases, and append-style migration drift so Atomy-Q API and WEB speak one canonical contract only.

**Architecture:** The API becomes the single source of truth for canonical database shape and canonical domain fields. Models, controllers, services, seeders, and tests must all use the same contract; no model-layer translation between old and new names remains. WEB consumes only the canonical API contract and stops normalizing legacy aliases.

**Tech Stack:** Laravel 11, PHP 8.3, Eloquent, FormRequest, JsonResource, PHPUnit, Next.js, TypeScript, Vitest, generated OpenAPI client.

---

### Task 1: API canonical schema and runtime cleanup

**Files:**
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000001_create_users_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000002_create_rfqs_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000005_create_vendor_invitations_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000006_create_quote_submissions_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000009_create_comparison_runs_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000018_create_decision_trail_entries_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_04_01_000001_create_vendors_table.php`
- Modify: `apps/atomy-q/API/database/migrations/2026_04_07_000005_create_identity_mfa_tables.php`
- Modify: `apps/atomy-q/API/app/Models/Vendor.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php`
- Modify: `apps/atomy-q/API/app/Services/Project/ProjectAclService.php`
- Modify: `apps/atomy-q/API/app/Services/Identity/AtomyUserQuery.php`
- Modify: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/AtomyDecisionTrailWriter.php`
- Modify: `apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`
- Modify: `apps/atomy-q/API/tests/Feature/VendorWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/ProjectsApiTest.php`
- Modify: `apps/atomy-q/API/tests/Unit/Models/ModelRelationsTest.php`
- Modify: `apps/atomy-q/API/tests/Unit/IdentityBindingsTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/API/README.md`
- Delete: `apps/atomy-q/API/database/migrations/2026_03_17_000001_add_project_id_to_rfqs_table.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_03_19_220000_add_uploaded_by_and_original_filename_to_quote_submissions_table.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_03_20_000001_users_email_unique_globally.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_03_20_000002_add_schedule_milestone_dates_to_rfqs_table.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_03_21_000001_make_submission_deadline_required_on_rfqs_table.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_03_24_000001_add_processing_fields_to_quote_submissions_table.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_04_03_000001_add_reminded_at_to_vendor_invitations_table.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_04_04_000000_add_intelligence_fields_to_normalization_source_lines.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_04_07_000002_update_users_table_for_gap_7.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_04_08_000008_add_tenant_id_to_identity_mfa_tables.php`
- Delete: `apps/atomy-q/API/database/migrations/2026_04_24_000031_add_summary_payload_to_decision_trail_entries_table.php`
- Delete: `apps/atomy-q/API/app/Services/Identity/AtomyLegacyRole.php`

- [ ] **Step 1: Write the failing tests for canonical-only behavior**

Target the current shim points before editing the runtime:
- vendor model should no longer populate old field names from canonical fields
- project ACL role checks should accept only canonical roles
- identity query should not manufacture a legacy role object from `users.role`
- decision-trail writer should expose only the typed write path
- migration freshness should succeed with the current schema captured in the owning `create_*_table` files

**Concrete failing assertions to add:**

1. **VendorRecommendationApiTest** — assert vendor model no longer populates legacy field names:
   ```php
   $this->assertArrayNotHasKey('recommended_reason_summary', $vendor->toArray());
   $this->assertArrayNotHasKey('legacy_field', $vendor->toArray());
   ```

2. **ProjectsApiTest** — assert ACL role checks reject non-canonical roles:
   ```php
   $response = $this->putJson("/api/v1/projects/{$id}/roles", ['roles' => [['user_id' => 'u1', 'role' => 'ADMIN']]]);
   $response->assertStatus(422); // Only 'admin' (lowercase) should be accepted
   ```

3. **IdentityGap7Test** — assert identity query does not construct legacy role object:
   ```php
   $user = User::find($id);
   $this->assertNull($user->legacy_role); // Should not exist
   ```

4. **RfqRecommendationDecisionTrailTest** — assert DecisionTrail writer only exposes typed write path:
   ```php
   $this->assertFalse(method_exists($trail, 'writeLegacy'));
   ```

5. **Migration freshness checks** — assert schema validation uses `create_*_table` files as source of truth:
   ```php
   $this->assertTrue(
     Schema::hasColumn('users', 'failed_login_attempts'),
     'Column must be defined in create_users_table.php'
   );
   ```

Run:
```bash
cd apps/atomy-q/API
php artisan test --filter VendorRecommendationApiTest
php artisan test --filter RfqRecommendationDecisionTrailTest
php artisan test --filter IdentityGap7Test
php artisan test --filter ProjectsApiTest
php artisan test --filter VendorWorkflowTest
```

- [ ] **Step 2: Fold schema changes into the owning create migrations**

Update the owning create files so they represent the final schema snapshot:
- `create_users_table.php` must include `failed_login_attempts`, `lockout_reason`, `lockout_expires_at`, `mfa_enabled`, and global email uniqueness
- `create_rfqs_table.php` must include `project_id`, `expected_award_at`, `technical_review_due_at`, and `financial_review_due_at`
- `create_vendors_table.php` must include the canonical vendor columns used by the model and controllers: `legal_name`, `display_name`, `country_of_registration`, `primary_contact_name`, `primary_contact_email`, `primary_contact_phone`, `approved_by_user_id`, `approved_at`, `approval_note`
- `create_quote_submissions_table.php` must include `uploaded_by`, `original_filename`, `processing_started_at`, `processing_completed_at`, `parsed_at`, and `retry_count`
- `create_vendor_invitations_table.php` must include `reminded_at`
- `create_decision_trail_entries_table.php` must include `summary_payload`
- `create_identity_mfa_tables.php` must include `tenant_id` directly on both MFA tables

After folding those columns, delete the now-obsolete add/alter migrations listed above.

- [ ] **Step 3: Remove runtime compatibility shims**

Strip the model/service/controller translation logic:
- remove `syncLegacyField()` and the `booted()` compatibility bridge from `Vendor`
- remove the role alias map from `ProjectController`
- remove the legacy role rank aliases from `ProjectAclService`
- remove the legacy single-role fallback branch from `AtomyUserQuery`
- remove `writeLegacy()` and the deprecated adapter path from `AtomyDecisionTrailWriter`
- delete `AtomyLegacyRole.php` once the query no longer references it

Keep only truthful runtime behavior:
- explicit validation failures remain
- explicit unavailable/error states remain
- no old field name is silently accepted or rewritten

- [ ] **Step 4: Rework seed data to match the canonical schema**

Update `PetrochemicalTenantSeeder.php` so inserted rows only use the canonical columns present in the final create migrations.
Remove any seed inserts or assertions that still rely on the deleted legacy columns.

- [ ] **Step 5: Re-run API verification**

Run:
```bash
cd apps/atomy-q/API
php artisan migrate:fresh --seed
php artisan test --filter VendorRecommendationApiTest
php artisan test --filter RfqRecommendationDecisionTrailTest
php artisan test --filter IdentityGap7Test
php artisan test --filter ProjectsApiTest
php artisan test --filter VendorWorkflowTest
php artisan test --filter ModelRelationsTest
```

Expected:
- migrations run cleanly from a fresh database
- tests pass without any legacy-field fallback behavior
- vendor/project/identity/decision-trail flows only use canonical shapes

### Task 2: WEB canonical contract cleanup

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.test.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/generated/api/types.gen.ts` after regeneration
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/README.md`

- [ ] **Step 1: Write the failing WEB tests for canonical vendor recommendation parsing**

Add/adjust tests so the hook rejects legacy candidate aliases and only accepts `eligible_candidates`, `excluded_candidates`, `provider_explanation`, `deterministic_reason_set`, and `provenance`.

Run:
```bash
cd apps/atomy-q/WEB
npx vitest run src/hooks/use-vendor-recommendations.test.ts src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx
```

- [ ] **Step 2: Remove legacy alias normalization from the hook**

Delete the parser branches that still accept old recommendation field names.
Keep live unavailable handling and truthful status rendering intact.

- [ ] **Step 3: Regenerate the API client and validate the WEB build**

Only after Task 1 (write failing WEB tests for canonical vendor recommendation parsing) is complete and the backend contract has been updated, regenerate the client and verify the frontend still compiles against the canonical payloads.

Run:
```bash
cd apps/atomy-q/WEB
npm run generate:api
npm run build
npx vitest run src/hooks/use-vendor-recommendations.test.ts src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx
```

- [ ] **Step 4: Remove obsolete docs language**

Rewrite WEB docs so they stop describing compatibility bridges or legacy parsing behavior.
Keep truthful runtime unavailable copy, because that is not compatibility logic.

### Task 3: Contract and docs sweep

**Files:**
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/API/README.md`
- Modify: `apps/atomy-q/WEB/README.md`
- Modify: any model- or migration-related notes in `docs/superpowers/specs/` or `docs/superpowers/plans/` that still describe compatibility shims as acceptable

- [ ] **Step 1: Remove legacy/compatibility wording from repo docs**

Replace old transitional phrasing with canonical-only language:
- no “legacy alias”
- no “backward compatibility”
- no “bridge” or “shim” language for schema or contract translation
- no instructions that tell people to keep both shapes alive

- [ ] **Step 2: Verify the docs still match the current code**

Run:
```bash
# Targeted search for compatibility terms (exclude false positives from type aliases)
rg -n "legacy|compatib|shim|bridge|backfill" apps/atomy-q/API apps/atomy-q/WEB docs/superpowers -g '*.md' -g '*.php'
# Alias only in comment/doc contexts
rg -n "^\s*(//|#|\*|<!--).*alias" apps/atomy-q/API apps/atomy-q/WEB docs/superpowers -g '*.md' -g '*.php' -g '*.ts' -g '*.tsx'
```

Expected:
- only truthful runtime availability wording remains
- no obsolete schema compatibility notes remain in the Atomy-Q app docs

