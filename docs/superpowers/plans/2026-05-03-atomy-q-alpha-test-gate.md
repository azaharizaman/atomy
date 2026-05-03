# Atomy-Q Alpha Test Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an alpha-first API and WEB test gate that proves the buyer alpha journey is tenant-safe, contract-aligned, live-mode honest, and backed by repeatable release evidence.

**Architecture:** The gate is layered: a QA matrix documents coverage, PHPUnit `alpha-gate` groups select API release evidence, Vitest covers WEB live-mode hook/data behavior, and Playwright specs run alpha journeys against the real local Laravel API. Existing domain feature files are extended where they already contain fixtures; new files are added only for shared contracts or missing release-gate structure.

**Tech Stack:** Laravel PHPUnit feature tests, `PHPUnit\Framework\Attributes\Group`, Laravel HTTP testing helpers, Vitest, React Testing Library, Playwright, Markdown QA docs, existing Atomy-Q root/API/WEB scripts.

---

## Change Impact After Main Update

`origin/main` now includes RFQ Evidence Vault as an approved alpha feature. The original plan treated Evidence Vault and generic Documents as deferred, which is no longer accurate.

Required plan changes:

- RFQ-scoped Evidence Vault is now part of the alpha gate.
- Top-level/global Documents and generic document-library behavior remain deferred.
- `tests/Feature/EvidenceVaultApiTest.php` must be included in the PHPUnit `alpha-gate` group.
- Shared API contract coverage must include RFQ Evidence Vault protected routes.
- WEB live-mode coverage should prioritize `src/hooks/use-evidence-vault.ts` because it already normalizes live API payloads and fails loudly on malformed data.
- The real-API Playwright alpha journey should include `/rfqs/{rfqId}/documents` when a seeded RFQ is available.

## Files And Responsibilities

- Create `apps/atomy-q/docs/05-qa/alpha-test-matrix.md`: alpha coverage source of truth with capability, API routes, WEB evidence, status, gaps, commands, and deferrals.
- Create `apps/atomy-q/API/tests/Feature/Api/AlphaGate/AlphaRouteContractTest.php`: shared auth/tenant/error-envelope contract tests for protected alpha routes.
- Modify selected API feature files by adding `#[Group('alpha-gate')]` and missing behavioral cases:
  - `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`
  - `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php`
  - `apps/atomy-q/API/tests/Feature/Api/V1/RfqInvitationApiTest.php`
  - `apps/atomy-q/API/tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`
  - `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
  - `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`
  - `apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php`
  - `apps/atomy-q/API/tests/Feature/ApprovalAlphaPathTest.php`
  - `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
  - `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`
  - `apps/atomy-q/API/tests/Feature/ProjectAclTest.php`
  - `apps/atomy-q/API/tests/Feature/TasksApiTest.php`
- Create or modify WEB hook tests near the relevant alpha hooks, prioritizing `apps/atomy-q/WEB/src/hooks/use-evidence-vault.test.ts` for RFQ Evidence Vault live-mode normalization.
- Modify or create `apps/atomy-q/WEB/tests/alpha-gate-real-api.spec.ts`: real-API Playwright release-gate journey.
- Modify `apps/atomy-q/docs/04-engineering/standards/testing-strategy.md`: document the alpha gate and release-evidence distinction.
- Modify root `package.json` only if there is no existing command that starts WEB and Laravel together for real-API E2E.

---

### Task 1: Create Alpha Test Matrix

**Files:**
- Create: `apps/atomy-q/docs/05-qa/alpha-test-matrix.md`
- Reference: `docs/superpowers/specs/2026-05-03-atomy-q-alpha-test-gate-design.md`
- Reference: `apps/atomy-q/docs/04-engineering/standards/testing-strategy.md`

- [ ] **Step 1: Write the matrix document**

Create `apps/atomy-q/docs/05-qa/alpha-test-matrix.md` with this structure:

```markdown
# Atomy-Q Alpha Test Matrix

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-05-03 | 1.0 | Initial alpha release-gate coverage matrix. |

## Purpose

This matrix defines which API and WEB tests count as Atomy-Q alpha release evidence. Smoke-only route checks are listed as smoke-only and do not count as feature coverage.

Status values:

- `behavioral`: tests prove success, failure, tenant boundary, and side effects for the capability.
- `contract`: tests prove shared status/error/shape guarantees but not full business behavior.
- `smoke-only`: tests only prove the route is reachable or not rejected by auth.
- `missing`: no release-gate evidence exists yet.
- `deferred`: intentionally outside the alpha gate.

## Alpha Capabilities

| Capability | API routes | WEB surface | Current evidence | Missing alpha cases | Required command | Status |
|---|---|---|---|---|---|---|
| Auth, session, MFA | `POST /api/v1/auth/login`, `POST /api/v1/auth/mfa/verify`, `POST /api/v1/auth/logout`, `POST /api/v1/auth/refresh` | Login and authenticated shell | `tests/Feature/IdentityGap7Test.php`, `tests/auth.spec.ts` | Mark API tests with `alpha-gate`; verify WEB real-API login path is in alpha gate | `php artisan test --group=alpha-gate`; `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` | `behavioral` |
| Tenant context and protected route contract | Alpha protected routes | Authenticated app shell | `tests/Feature/Api/MiddlewareTest.php`, `tests/Feature/Api/ProtectedEndpointsTest.php` | Add shared `AlphaRouteContractTest` for no token, invalid token, missing tenant, validation envelope | `php artisan test --group=alpha-gate` | `contract` |
| Projects and task visibility | `GET /api/v1/projects`, `GET /api/v1/projects/{id}`, `GET /api/v1/tasks`, `GET /api/v1/tasks/{id}` | Projects/tasks alpha screens | `tests/Feature/ProjectAclTest.php`, `tests/Feature/TasksApiTest.php`, `tests/projects-tasks-smoke.spec.ts` | Mark gate tests; add missing WEB real-API assertion if journey depends on project task visibility | `php artisan test --group=alpha-gate`; `npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` | `behavioral` |
| RFQ lifecycle | `POST /api/v1/rfqs`, `PUT /api/v1/rfqs/{id}`, `PUT /api/v1/rfqs/{id}/draft`, `PATCH /api/v1/rfqs/{id}/status`, `POST /api/v1/rfqs/{id}/duplicate`, `POST /api/v1/rfqs/bulk-action` | RFQ alpha journey | `tests/Feature/Api/RfqLifecycleMutationTest.php`, `tests/Feature/Api/RfqLifecycleIdempotencyTest.php`, `tests/rfq-lifecycle-e2e.spec.ts` | Add missing invalid payload and no-side-effect assertions for alpha mutations | `php artisan test --group=alpha-gate` | `behavioral` |
| Vendor selection and invitations | `GET /api/v1/rfqs/{id}/selected-vendors`, `PUT /api/v1/rfqs/{id}/selected-vendors`, `GET /api/v1/rfqs/{id}/invitations`, `POST /api/v1/rfqs/{id}/invitations`, `POST /api/v1/rfqs/{id}/invitations/{invId}/remind` | RFQ vendor step | `tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`, `tests/Feature/Api/V1/RfqInvitationApiTest.php`, `tests/Feature/Api/RfqInvitationReminderTest.php` | Add idempotency replay and failure no-mail/no-job assertions where missing | `php artisan test --group=alpha-gate` | `behavioral` |
| Vendor recommendations | `POST /api/v1/rfqs/{id}/vendor-recommendations` | Recommendation panel | `tests/Feature/Api/V1/VendorRecommendationApiTest.php`, `tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`, `tests/provider-sourcing-recommendation-e2e.spec.ts` | Mark API tests; ensure real-API WEB journey has provider-unavailable and successful-provider evidence split | `php artisan test --group=alpha-gate`; `npm run test:e2e:provider-sourcing-recommendation:fake` | `behavioral` |
| Quote intake | `POST /api/v1/quote-submissions/upload`, `GET /api/v1/quote-submissions`, `GET /api/v1/quote-submissions/{id}`, `PATCH /api/v1/quote-submissions/{id}/status` | Quote upload/intake screens | `tests/Feature/QuoteSubmissionWorkflowTest.php`, `tests/provider-quote-e2e.spec.ts` | Add no-job-on-invalid and file storage failure assertions if not already covered | `php artisan test --group=alpha-gate`; `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` | `behavioral` |
| Normalization review | `GET /api/v1/normalization/{rfqId}/source-lines`, `POST /api/v1/quote-submissions/{id}/source-lines`, `PATCH /api/v1/quote-submissions/{id}/source-lines/{sourceLineId}`, `DELETE /api/v1/quote-submissions/{id}/source-lines/{sourceLineId}`, `PUT /api/v1/normalization/source-lines/{id}/override` | Normalization review screen | `tests/Feature/NormalizationReviewWorkflowTest.php`, `tests/provider-normalization-degraded.spec.ts` | Mark gate tests; add WEB live-mode malformed/undefined source-line payload tests | `php artisan test --group=alpha-gate`; `npm run test:unit` | `behavioral` |
| Comparison | `POST /api/v1/comparison-runs/preview`, `POST /api/v1/comparison-runs/final`, `GET /api/v1/comparison-runs/{id}/matrix`, `GET /api/v1/comparison-runs/{id}/readiness`, `GET /api/v1/comparison-runs/{id}/overlay` | Comparison workspace | `tests/Feature/ComparisonRunWorkflowTest.php` | Mark gate tests; ensure rollback/no-partial-persistence and decision trail assertions are grouped | `php artisan test --group=alpha-gate` | `behavioral` |
| Approvals | `GET /api/v1/approvals`, `GET /api/v1/approvals/{id}`, `POST /api/v1/approvals/{id}/approve`, `POST /api/v1/approvals/{id}/reject`, `GET /api/v1/approvals/{id}/summary`, `POST /api/v1/approvals/{id}/summary/generate` | Approval screen | `tests/Feature/ApprovalAlphaPathTest.php` | Add no-success-artifact-on-failure assertion if missing | `php artisan test --group=alpha-gate` | `behavioral` |
| Awards | `POST /api/v1/awards`, `POST /api/v1/awards/{id}/signoff`, `POST /api/v1/awards/{id}/debrief/{vendorId}`, `GET /api/v1/awards/{id}/guidance`, `POST /api/v1/awards/{id}/guidance/generate` | Award/signoff/debrief screens | `tests/Feature/AwardWorkflowTest.php` | Mark gate tests; assert repeat signoff/debrief idempotence in grouped tests | `php artisan test --group=alpha-gate` | `behavioral` |
| RFQ Evidence Vault | `GET /api/v1/rfqs/{id}/evidence-vault`, `POST /api/v1/rfqs/{id}/evidence-vault/supporting-evidence`, `POST /api/v1/rfqs/{id}/evidence-vault/award-pack/finalize`, `GET /api/v1/rfqs/{id}/evidence-vault/award-pack/export`; removed generic `GET /api/v1/documents` and `GET /api/v1/evidence-bundles` | RFQ workspace Evidence Vault at `/rfqs/{rfqId}/documents` | `tests/Feature/EvidenceVaultApiTest.php`, `src/hooks/use-evidence-vault.test.ts`, `src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx` | Mark API tests with `alpha-gate`; include Evidence Vault routes in shared contract tests; add WEB live-mode valid, undefined, malformed, empty, and transport-failure cases; add real-API Playwright assertion when seeded RFQ is available | `php artisan test --group=alpha-gate`; `npx vitest run src/hooks/use-evidence-vault.test.ts src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx` | `behavioral` |
| Decision trail evidence | `GET /api/v1/decision-trail`, `GET /api/v1/decision-trail/{id}` | Decision trail panels | Multiple workflow tests | Add matrix references to exact workflow assertions after grouping | `php artisan test --group=alpha-gate` | `behavioral` |

## Deferred After Alpha Gate

| Route family | Reason | Follow-up expectation |
|---|---|---|
| Account settings and payment methods | Not required to prove buyer procurement alpha journey | Add CRUD/validation/auth/tenant feature tests in post-alpha hardening |
| Reports schedules and exports | Alpha journey only needs dashboard/report summary evidence | Add schedule/export/download tests in post-alpha hardening |
| Integrations and API monitor | Operational surface, not buyer alpha path | Add external integration fake/error tests in post-alpha hardening |
| Handoffs | PO/contract handoff is not release-gate path for current alpha | Add workflow, retry, and external failure tests in post-alpha hardening |
| Notifications | Covered only where alpha jobs/mail matter | Add notification list/read/clear feature tests in post-alpha hardening |
| Top-level Documents and generic document library | Alpha supports only RFQ-scoped Evidence Vault, not a global document-management surface | Add global document-library CRUD/download/preview tests only if a later approved scope reintroduces that product surface |
```

- [ ] **Step 2: Verify the document has no placeholder language**

Run:

```bash
rg -n "TBD|TODO|placeholder|later|fill in" apps/atomy-q/docs/05-qa/alpha-test-matrix.md
```

Expected: no output.

- [ ] **Step 3: Commit the matrix**

```bash
git add apps/atomy-q/docs/05-qa/alpha-test-matrix.md
git commit -m "docs: add atomy-q alpha test matrix"
```

---

### Task 2: Add Shared API Alpha Contract Tests

**Files:**
- Create: `apps/atomy-q/API/tests/Feature/Api/AlphaGate/AlphaRouteContractTest.php`
- Reference: `apps/atomy-q/API/tests/Feature/Api/ApiTestCase.php`
- Reference: `apps/atomy-q/API/tests/Feature/Api/MiddlewareTest.php`

- [ ] **Step 1: Write the failing shared contract test**

Create `apps/atomy-q/API/tests/Feature/Api/AlphaGate/AlphaRouteContractTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\AlphaGate;

use App\Contracts\JwtServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Api\ApiTestCase;

#[Group('alpha-gate')]
final class AlphaRouteContractTest extends ApiTestCase
{
    #[DataProvider('protectedAlphaRoutes')]
    public function test_protected_alpha_routes_reject_missing_token(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Authentication required']);
    }

    #[DataProvider('protectedAlphaRoutes')]
    public function test_protected_alpha_routes_reject_invalid_token(string $method, string $uri): void
    {
        $response = $this->json($method, $uri, [], [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Invalid or expired token']);
    }

    #[DataProvider('protectedAlphaRoutes')]
    public function test_protected_alpha_routes_reject_missing_tenant_context(string $method, string $uri): void
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken('alpha-user', '');

        $response = $this->json($method, $uri, [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'Tenant context required']);
    }

    public function test_alpha_validation_errors_use_canonical_envelope(): void
    {
        $response = $this->postJson('/api/v1/rfqs', [], $this->authHeaders());

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.title.0', 'The title field is required.');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedAlphaRoutes(): array
    {
        return [
            'dashboard kpis' => ['GET', '/api/v1/dashboard/kpis'],
            'rfq list' => ['GET', '/api/v1/rfqs'],
            'rfq counts' => ['GET', '/api/v1/rfqs/counts'],
            'vendor list' => ['GET', '/api/v1/vendors'],
            'quote list' => ['GET', '/api/v1/quote-submissions'],
            'comparison list' => ['GET', '/api/v1/comparison-runs'],
            'approval list' => ['GET', '/api/v1/approvals'],
            'award list' => ['GET', '/api/v1/awards'],
            'rfq evidence vault summary' => ['GET', '/api/v1/rfqs/rfq-alpha/evidence-vault'],
            'rfq evidence vault supporting evidence upload' => ['POST', '/api/v1/rfqs/rfq-alpha/evidence-vault/supporting-evidence'],
            'rfq evidence vault finalize' => ['POST', '/api/v1/rfqs/rfq-alpha/evidence-vault/award-pack/finalize'],
            'rfq evidence vault export' => ['GET', '/api/v1/rfqs/rfq-alpha/evidence-vault/award-pack/export'],
            'decision trail list' => ['GET', '/api/v1/decision-trail'],
            'project list' => ['GET', '/api/v1/projects'],
            'task list' => ['GET', '/api/v1/tasks'],
        ];
    }
}
```

- [ ] **Step 2: Run the contract test and capture failures**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/AlphaGate/AlphaRouteContractTest.php
```

Expected before fixes: failures are acceptable only if an error envelope differs from the asserted canonical shape. Auth and tenant middleware assertions should pass if existing middleware behavior is unchanged.

- [ ] **Step 3: Adjust only the test expectations if the documented canonical envelope differs**

If the existing API validation envelope for RFQ creation uses `message` instead of `error`, inspect:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/AlphaGate/AlphaRouteContractTest.php --filter alpha_validation_errors
```

Then update the assertion to the actual canonical envelope used by alpha API tests. Do not alter application behavior in this task unless the envelope contradicts existing documented API standards.

- [ ] **Step 4: Run grouped API gate**

Run:

```bash
cd apps/atomy-q/API
php artisan test --group=alpha-gate
```

Expected: the new contract test passes.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/tests/Feature/Api/AlphaGate/AlphaRouteContractTest.php
git commit -m "test(api): add alpha route contract gate"
```

---

### Task 3: Mark Existing API Alpha Behavioral Tests

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RfqInvitationApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/ApprovalAlphaPathTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/ProjectAclTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/TasksApiTest.php`

- [ ] **Step 1: Add the PHPUnit group import and class attribute**

In each listed file, add this import with the existing `use` block:

```php
use PHPUnit\Framework\Attributes\Group;
```

Add this attribute immediately above the class declaration:

```php
#[Group('alpha-gate')]
```

Example:

```php
use PHPUnit\Framework\Attributes\Group;

#[Group('alpha-gate')]
final class RfqLifecycleMutationTest extends ApiTestCase
{
    // existing tests
}
```

For non-final classes, keep the existing class keyword and add only the attribute.

- [ ] **Step 2: Run the grouped API gate**

Run:

```bash
cd apps/atomy-q/API
php artisan test --group=alpha-gate
```

Expected: all marked tests run. Failures indicate existing alpha behavior is already broken or an attribute/import conflict was introduced.

- [ ] **Step 3: Run focused syntax check on modified files**

Run:

```bash
cd apps/atomy-q/API
for file in \
  tests/Feature/IdentityGap7Test.php \
  tests/Feature/Api/RfqLifecycleMutationTest.php \
  tests/Feature/Api/RfqLifecycleIdempotencyTest.php \
  tests/Feature/Api/V1/RfqInvitationApiTest.php \
  tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php \
  tests/Feature/QuoteSubmissionWorkflowTest.php \
  tests/Feature/NormalizationReviewWorkflowTest.php \
  tests/Feature/ComparisonRunWorkflowTest.php \
  tests/Feature/ApprovalAlphaPathTest.php \
  tests/Feature/AwardWorkflowTest.php \
  tests/Feature/EvidenceVaultApiTest.php \
  tests/Feature/ProjectAclTest.php \
  tests/Feature/TasksApiTest.php; do php -l "$file"; done
```

Expected: each file reports `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add \
  apps/atomy-q/API/tests/Feature/IdentityGap7Test.php \
  apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php \
  apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/RfqInvitationApiTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php \
  apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/ApprovalAlphaPathTest.php \
  apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php \
  apps/atomy-q/API/tests/Feature/ProjectAclTest.php \
  apps/atomy-q/API/tests/Feature/TasksApiTest.php
git commit -m "test(api): tag alpha behavioral coverage"
```

---

### Task 4: Backfill API No-Side-Effect And Idempotency Cases

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RfqInvitationApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`

- [ ] **Step 1: Add RFQ duplicate replay assertion**

In `RfqLifecycleIdempotencyTest.php`, add a test that posts to `POST /api/v1/rfqs/{rfqId}/duplicate` twice with the same `Idempotency-Key` and asserts there is only one duplicated RFQ for the original source. Use existing RFQ/user factory helpers from the file.

Test shape:

```php
public function test_duplicate_replay_does_not_create_second_copy(): void
{
    $tenantId = (string) Str::ulid();
    $user = $this->createUser($tenantId);
    $rfq = Rfq::factory()->create([
        'tenant_id' => $tenantId,
        'created_by' => (string) $user->id,
        'title' => 'Alpha source RFQ',
        'status' => 'draft',
    ]);

    $headers = $this->authHeaders($tenantId, (string) $user->id, 'alpha-duplicate-replay-key');

    $first = $this->postJson('/api/v1/rfqs/' . $rfq->id . '/duplicate', [], $headers);
    $second = $this->postJson('/api/v1/rfqs/' . $rfq->id . '/duplicate', [], $headers);

    $first->assertCreated();
    $second->assertCreated();
    $this->assertSame($first->json('data.id'), $second->json('data.id'));
    $this->assertDatabaseCount('rfqs', 2);
}
```

- [ ] **Step 2: Add invitation invalid payload no-side-effect assertion**

In `RfqInvitationApiTest.php`, add a test for `POST /api/v1/rfqs/{rfqId}/invitations` with an invalid `vendor_id`. Assert `422` and no `vendor_invitations` row for the RFQ.

Test shape:

```php
public function test_invitation_invalid_vendor_does_not_create_invitation(): void
{
    $tenantId = (string) Str::ulid();
    $user = $this->createUser($tenantId);
    $rfq = $this->createRfq($tenantId, (string) $user->id);

    $response = $this->postJson('/api/v1/rfqs/' . $rfq->id . '/invitations', [
        'vendor_id' => (string) Str::ulid(),
    ], $this->authHeaders($tenantId, (string) $user->id, 'alpha-invalid-invite-key'));

    $response->assertUnprocessable();
    $this->assertDatabaseMissing('vendor_invitations', [
        'tenant_id' => $tenantId,
        'rfq_id' => (string) $rfq->id,
    ]);
}
```

- [ ] **Step 3: Add quote upload invalid payload no-job assertion**

In `QuoteSubmissionWorkflowTest.php`, add `Queue::fake()` to the existing required-field invalid upload test or add a new test. Assert `ProcessQuoteSubmissionJob` is not pushed when validation fails.

Code to add:

```php
Queue::fake();

$response = $this->postJson('/api/v1/quote-submissions/upload', [], $headers);

$response->assertStatus(422);
Queue::assertNotPushed(ProcessQuoteSubmissionJob::class);
```

- [ ] **Step 4: Add award signoff repeat behavior under alpha group**

In `AwardWorkflowTest.php`, confirm the existing repeat signoff assertion is in a grouped test. If no focused test exists, add:

```php
public function test_award_signoff_repeat_is_stable_for_alpha_gate(): void
{
    $tenantId = (string) Str::ulid();
    $user = $this->createUser($tenantId);
    $award = $this->createAward($tenantId, [
        'status' => 'pending',
    ]);

    $headers = $this->authHeaders((string) $user->id, $tenantId);

    $first = $this->postJson('/api/v1/awards/' . $award->id . '/signoff', [], $headers);
    $second = $this->postJson('/api/v1/awards/' . $award->id . '/signoff', [], $headers);

    $first->assertOk();
    $second->assertOk();
    $this->assertDatabaseHas('awards', [
        'id' => (string) $award->id,
        'tenant_id' => $tenantId,
        'status' => 'signed_off',
    ]);
}
```

If helper names differ in `AwardWorkflowTest.php`, reuse the exact helper names already present in that file and keep the same assertions.

- [ ] **Step 5: Run focused tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test \
  tests/Feature/Api/RfqLifecycleIdempotencyTest.php \
  tests/Feature/Api/V1/RfqInvitationApiTest.php \
  tests/Feature/QuoteSubmissionWorkflowTest.php \
  tests/Feature/AwardWorkflowTest.php
```

Expected: all four files pass.

- [ ] **Step 6: Run grouped API gate**

Run:

```bash
cd apps/atomy-q/API
php artisan test --group=alpha-gate
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add \
  apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/RfqInvitationApiTest.php \
  apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php
git commit -m "test(api): backfill alpha mutation invariants"
```

---

### Task 5: Add WEB Live-Mode Unit Coverage

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-evidence-vault.test.ts`
- Create or modify: `apps/atomy-q/WEB/src/hooks/__tests__/alphaLiveMode.test.tsx` only if no hook-local test file exists for the selected hook.
- Reference: `apps/atomy-q/WEB/src/test/setup.ts`
- Reference: `apps/atomy-q/WEB/src/test/utils.tsx`
- Reference: `apps/atomy-q/WEB/src/hooks`

- [ ] **Step 1: Identify alpha API hooks**

Run:

```bash
cd apps/atomy-q/WEB
rg -n "NEXT_PUBLIC_USE_MOCKS|fetch\\(|axios|useQuery|quote|normalization|comparison|approval|award|rfq" src/hooks src/lib src/store
```

Expected: list of hooks/adapters that consume alpha API data. Because RFQ Evidence Vault is now an alpha feature, choose `use-evidence-vault.ts` first unless current code shows it already has all required live-mode cases.

- [ ] **Step 2: Write a failing live-mode malformed-payload test**

Extend `apps/atomy-q/WEB/src/hooks/use-evidence-vault.test.ts` if it remains the closest hook-specific test. Create `apps/atomy-q/WEB/src/hooks/__tests__/alphaLiveMode.test.tsx` only if the selected hook has no closer test. The test must mock the transport response, set live mode, and assert malformed payload produces an error/unavailable state rather than success.

Template for a hook that uses global `fetch`:

```tsx
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestQueryClientWrapper } from '@/test/utils';
import { useAlphaComparisonReadiness } from '../useAlphaComparisonReadiness';

describe('alpha live-mode API handling', () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    vi.stubEnv('NEXT_PUBLIC_USE_MOCKS', 'false');
  });

  afterEach(() => {
    global.fetch = originalFetch;
    vi.unstubAllEnvs();
    vi.restoreAllMocks();
  });

  it('treats malformed live comparison readiness payload as unavailable', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ unexpected: true }),
    } as Response);

    const { result } = renderHook(
      () => useAlphaComparisonReadiness('run-alpha'),
      { wrapper: createTestQueryClientWrapper() },
    );

    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(result.current.isError || result.current.data?.available === false).toBe(true);
  });
});
```

Replace `useAlphaComparisonReadiness` and result property names with the actual hook from Step 1. Keep the assertion strict: malformed live data cannot become a successful populated state.

- [ ] **Step 3: Add valid, empty, and transport-failure cases**

In the same test file, add three cases for the same hook:

```tsx
it('accepts valid live payload', async () => {
  global.fetch = vi.fn().mockResolvedValue({
    ok: true,
    status: 200,
    json: async () => ({
      data: {
        id: 'run-alpha',
        readiness: { is_ready: true },
      },
    }),
  } as Response);

  const { result } = renderHook(
    () => useAlphaComparisonReadiness('run-alpha'),
    { wrapper: createTestQueryClientWrapper() },
  );

  await waitFor(() => expect(result.current.isLoading).toBe(false));

  expect(result.current.isError).toBe(false);
  expect(result.current.data).toBeDefined();
});

it('renders empty tenant-scoped live payload honestly', async () => {
  global.fetch = vi.fn().mockResolvedValue({
    ok: true,
    status: 200,
    json: async () => ({ data: [] }),
  } as Response);

  const { result } = renderHook(
    () => useAlphaComparisonReadiness('run-alpha'),
    { wrapper: createTestQueryClientWrapper() },
  );

  await waitFor(() => expect(result.current.isLoading).toBe(false));

  expect(result.current.isError || Array.isArray(result.current.data)).toBe(true);
});

it('surfaces live transport failure', async () => {
  global.fetch = vi.fn().mockResolvedValue({
    ok: false,
    status: 500,
    json: async () => ({ error: 'Server error' }),
  } as Response);

  const { result } = renderHook(
    () => useAlphaComparisonReadiness('run-alpha'),
    { wrapper: createTestQueryClientWrapper() },
  );

  await waitFor(() => expect(result.current.isLoading).toBe(false));

  expect(result.current.isError || result.current.data?.available === false).toBe(true);
});
```

Again, replace hook and property names with actual names from the selected hook.

- [ ] **Step 4: Run WEB unit tests**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit
```

Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-evidence-vault.test.ts apps/atomy-q/WEB/src/hooks/__tests__/alphaLiveMode.test.tsx
git commit -m "test(web): add alpha live-mode hook coverage"
```

---

### Task 6: Add Real-API Playwright Alpha Gate Spec

**Files:**
- Create or modify: `apps/atomy-q/WEB/tests/alpha-gate-real-api.spec.ts`
- Reference: `apps/atomy-q/WEB/tests/playwright-auth-bootstrap.ts`
- Reference: `apps/atomy-q/WEB/tests/alpha-playwright-bootstrap.ts`
- Reference: `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`
- Reference: `apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts`
- Reference: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`

- [ ] **Step 1: Inspect existing bootstrap helpers**

Run:

```bash
cd apps/atomy-q/WEB
sed -n '1,220p' tests/playwright-auth-bootstrap.ts
sed -n '1,220p' tests/alpha-playwright-bootstrap.ts
```

Expected: identify the helper that logs in or seeds auth state for alpha tests.

- [ ] **Step 2: Create the real-API alpha spec**

Create `apps/atomy-q/WEB/tests/alpha-gate-real-api.spec.ts`:

```ts
import { expect, test } from '@playwright/test';
import { bootstrapAlphaAuth } from './alpha-playwright-bootstrap';

test.describe('alpha-gate real API journey', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(process.env.NEXT_PUBLIC_USE_MOCKS === 'true', 'alpha gate requires real API mode');
    await bootstrapAlphaAuth(page);
  });

  test('buyer can navigate the alpha procurement evidence path', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page.getByRole('heading', { name: /dashboard|workspace|rfq/i })).toBeVisible();

    await page.goto('/rfqs');
    await expect(page.getByText(/rfq|request for quotation/i).first()).toBeVisible();

    await page.goto('/vendors');
    await expect(page.getByText(/vendor/i).first()).toBeVisible();

    await page.goto('/approvals');
    await expect(page.getByText(/approval/i).first()).toBeVisible();

    await page.goto('/awards');
    await expect(page.getByText(/award/i).first()).toBeVisible();

    const firstRfqLink = page.getByRole('link', { name: /rfq|request for quotation/i }).first();
    if (await firstRfqLink.isVisible().catch(() => false)) {
      await firstRfqLink.click();
      await page.getByRole('link', { name: /evidence vault/i }).click();
      await expect(page.getByRole('heading', { name: /evidence vault/i })).toBeVisible();
    }
  });
});
```

If the project uses different route paths, helper names, or seeded RFQ selectors, update the imports and paths to match the existing Playwright specs. Keep `test.skip(process.env.NEXT_PUBLIC_USE_MOCKS === 'true', ...)` so this spec cannot accidentally count mock mode as release evidence. The Evidence Vault assertion may be seeded-RFQ dependent, but it should be included whenever the local alpha seed provides an RFQ workspace route.

- [ ] **Step 3: Run the spec in real-API mode**

Run with the existing local server setup:

```bash
cd apps/atomy-q/WEB
NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts
```

Expected: pass when WEB and Laravel API are available according to existing Playwright config.

- [ ] **Step 4: If server startup is missing, add one root command**

Inspect root scripts:

```bash
cat package.json
```

If no script starts Laravel and WEB for E2E, add one script named `test:e2e:alpha-gate` using the repo's existing process manager pattern. Do not add a second command if `npm run test:e2e:laravel` already covers this.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/tests/alpha-gate-real-api.spec.ts package.json
git commit -m "test(web): add real-api alpha gate journey"
```

If `package.json` was not changed, omit it from `git add`.

---

### Task 7: Document Gate Commands And Evidence Rules

**Files:**
- Modify: `apps/atomy-q/docs/04-engineering/standards/testing-strategy.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`

- [ ] **Step 1: Add alpha gate section to testing strategy**

Append this section to `apps/atomy-q/docs/04-engineering/standards/testing-strategy.md`:

````markdown
## Alpha Release Gate

The alpha release gate is the minimum automated evidence set for the buyer procurement alpha journey.

API release evidence:

```bash
cd apps/atomy-q/API
php artisan migrate:fresh --seed
php artisan test --group=alpha-gate
```

WEB release evidence:

```bash
cd apps/atomy-q/WEB
npm run test:unit
NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts
```

Root release evidence, when available:

```bash
npm run test:e2e:laravel
```

Mocked WEB E2E tests are fast regression checks. They do not count as alpha release evidence unless the same journey also passes with `NEXT_PUBLIC_USE_MOCKS=false` against the real local Laravel API.

The alpha matrix lives at `apps/atomy-q/docs/05-qa/alpha-test-matrix.md`. It must mark each capability as `behavioral`, `contract`, `smoke-only`, `missing`, or `deferred`.
````

- [ ] **Step 2: Update release checklist evidence reference**

In `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`, add an alpha test gate checklist item in the current verification section:

```markdown
- [ ] Alpha test gate passed:
  - API: `php artisan test --group=alpha-gate`
  - WEB unit: `npm run test:unit`
  - WEB real-API E2E: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts`
  - Matrix: `apps/atomy-q/docs/05-qa/alpha-test-matrix.md`
```

If the checklist uses a table instead of checkboxes, add an equivalent row with those exact commands.

- [ ] **Step 3: Verify docs have no placeholders**

Run:

```bash
rg -n "TBD|TODO|placeholder|later|fill in" \
  apps/atomy-q/docs/04-engineering/standards/testing-strategy.md \
  apps/atomy-q/docs/02-release-management/current-release/release-checklist.md
```

Expected: no newly introduced placeholder lines.

- [ ] **Step 4: Commit**

```bash
git add \
  apps/atomy-q/docs/04-engineering/standards/testing-strategy.md \
  apps/atomy-q/docs/02-release-management/current-release/release-checklist.md
git commit -m "docs: document atomy-q alpha test gate"
```

---

### Task 8: Run Full Alpha Gate Verification

**Files:**
- No source files should be modified in this task.

- [ ] **Step 1: Run API alpha gate**

Run:

```bash
cd apps/atomy-q/API
php artisan migrate:fresh --seed
php artisan test --group=alpha-gate
```

Expected: pass.

- [ ] **Step 2: Run WEB unit tests**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit
```

Expected: pass.

- [ ] **Step 3: Run real-API Playwright alpha spec**

Run:

```bash
cd apps/atomy-q/WEB
NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts
```

Expected: pass with Laravel API available through existing Playwright setup.

- [ ] **Step 4: Run root Laravel E2E gate if available**

Run:

```bash
npm run test:e2e:laravel
```

Expected: pass. If the root script is unavailable, record that the plan used the WEB-level real-API Playwright command from Step 3.

- [ ] **Step 5: Commit verification-only documentation updates if any were needed**

If verification revealed command names had to be corrected in docs, commit only those documentation corrections:

```bash
git add apps/atomy-q/docs/04-engineering/standards/testing-strategy.md apps/atomy-q/docs/02-release-management/current-release/release-checklist.md apps/atomy-q/docs/05-qa/alpha-test-matrix.md
git commit -m "docs: align alpha test gate commands"
```

If no files changed, do not create a commit.

---

## Self-Review Notes

- Spec coverage: the plan covers matrix creation, API shared contract tests, API behavioral grouping, idempotency/no-side-effect backfill, WEB live-mode unit coverage, real-API Playwright release evidence, release docs, and final verification.
- Placeholder scan: plan text avoids `TBD`, `TODO`, and unspecified "fill in" instructions. Where exact hook/helper names may vary, the plan requires discovery commands and replacement with actual local names before writing code.
- Type consistency: API examples use Laravel/PHPUnit conventions already present in the repo; WEB examples use Vitest, React Testing Library, and Playwright patterns already present in `apps/atomy-q/WEB`.
