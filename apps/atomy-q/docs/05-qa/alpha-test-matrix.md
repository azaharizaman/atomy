# Atomy-Q Alpha Test Matrix

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-05-03 | 1.0 | Initial alpha release-gate coverage matrix. |

## Purpose

Only this matrix defines which automated tests count as alpha release-gate coverage evidence; release checklists record whether those commands passed for a release. Smoke-only route checks are listed as smoke-only and do not count as feature coverage.

Status values:

- `behavioral`: tests prove success, failure, tenant boundary, and side effects for the capability.
- `contract`: tests prove shared status/error/shape guarantees but not full business behavior.
- `smoke-only`: tests only prove the route is reachable or not rejected by auth.
- `missing`: no release-gate evidence exists yet.
- `deferred`: intentionally outside the alpha gate.

## Alpha Capabilities

| Capability | API routes | WEB surface | Current evidence | Missing alpha cases | Required command | Status |
|---|---|---|---|---|---|---|
| Auth, session, MFA | `POST /api/v1/auth/login`, `POST /api/v1/auth/mfa/verify`, `POST /api/v1/auth/logout`, `POST /api/v1/auth/refresh` | Login and authenticated shell | API: `tests/Feature/IdentityGap7Test.php`; WEB: `tests/auth.spec.ts` | Mark API tests with `alpha-gate`; missing: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` for WEB real-API login path | `php artisan test --group=alpha-gate` | `behavioral` |
| Tenant context and protected route contract | Alpha protected routes | Authenticated app shell | API: `tests/Feature/Api/MiddlewareTest.php`, `tests/Feature/Api/ProtectedEndpointsTest.php` | Add shared `AlphaRouteContractTest` for no token, invalid token, missing tenant, validation envelope | `php artisan test --group=alpha-gate` | `contract` |
| Projects and task visibility | `GET /api/v1/projects`, `GET /api/v1/projects/{id}`, `GET /api/v1/tasks`, `GET /api/v1/tasks/{id}` | Projects/tasks alpha screens | API: `tests/Feature/ProjectAclTest.php`, `tests/Feature/TasksApiTest.php`; WEB smoke/local: `tests/projects-tasks-smoke.spec.ts` | Mark gate tests; missing: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` if journey depends on project task visibility | `php artisan test --group=alpha-gate` | `behavioral` |
| RFQ lifecycle | `POST /api/v1/rfqs`, `PUT /api/v1/rfqs/{id}`, `PUT /api/v1/rfqs/{id}/draft`, `PATCH /api/v1/rfqs/{id}/status`, `POST /api/v1/rfqs/{id}/duplicate`, `POST /api/v1/rfqs/bulk-action` | RFQ alpha journey | API: `tests/Feature/Api/RfqLifecycleMutationTest.php`, `tests/Feature/Api/RfqLifecycleIdempotencyTest.php`; WEB: `tests/rfq-lifecycle-e2e.spec.ts` | Add missing invalid payload and no-side-effect assertions for alpha mutations | `php artisan test --group=alpha-gate` | `behavioral` |
| Vendor selection and invitations | `GET /api/v1/rfqs/{id}/selected-vendors`, `PUT /api/v1/rfqs/{id}/selected-vendors`, `GET /api/v1/rfqs/{id}/invitations`, `POST /api/v1/rfqs/{id}/invitations`, `POST /api/v1/rfqs/{id}/invitations/{invId}/remind` | RFQ vendor step | API: `tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`, `tests/Feature/Api/V1/RfqInvitationApiTest.php`, `tests/Feature/Api/RfqInvitationReminderTest.php` | Add idempotency replay and failure no-mail/no-job assertions where missing | `php artisan test --group=alpha-gate` | `behavioral` |
| Vendor recommendations | `POST /api/v1/rfqs/{id}/vendor-recommendations` | Recommendation panel | API: `tests/Feature/Api/V1/VendorRecommendationApiTest.php`, `tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`; WEB mock/local regression only: `tests/provider-sourcing-recommendation-e2e.spec.ts` | Mark API tests; missing: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` for provider-unavailable and successful-provider real-API WEB evidence | `php artisan test --group=alpha-gate` | `behavioral` |
| Quote intake | `POST /api/v1/quote-submissions/upload`, `GET /api/v1/quote-submissions`, `GET /api/v1/quote-submissions/{id}`, `PATCH /api/v1/quote-submissions/{id}/status` | Quote upload/intake screens | API: `tests/Feature/QuoteSubmissionWorkflowTest.php`; WEB mock/local regression only: `tests/provider-quote-e2e.spec.ts` | Add no-job-on-invalid and file storage failure assertions if not already covered; missing: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` for WEB real-API quote intake evidence | `php artisan test --group=alpha-gate` | `behavioral` |
| Normalization review | `GET /api/v1/normalization/{rfqId}/source-lines`, `POST /api/v1/quote-submissions/{id}/source-lines`, `PATCH /api/v1/quote-submissions/{id}/source-lines/{sourceLineId}`, `DELETE /api/v1/quote-submissions/{id}/source-lines/{sourceLineId}`, `PUT /api/v1/normalization/source-lines/{id}/override` | Normalization review screen | API: `tests/Feature/NormalizationReviewWorkflowTest.php`; WEB mock/local regression only: `tests/provider-normalization-degraded.spec.ts` | Mark gate tests; add WEB live-mode malformed/undefined source-line payload tests; missing: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` for WEB real-API normalization evidence | `php artisan test --group=alpha-gate`; `npm run test:unit` | `behavioral` |
| Comparison | `POST /api/v1/comparison-runs/preview`, `POST /api/v1/comparison-runs/final`, `GET /api/v1/comparison-runs/{id}/matrix`, `GET /api/v1/comparison-runs/{id}/readiness`, `GET /api/v1/comparison-runs/{id}/overlay` | Comparison workspace | API: `tests/Feature/ComparisonRunWorkflowTest.php` | Mark gate tests; ensure rollback/no-partial-persistence and decision trail assertions are grouped | `php artisan test --group=alpha-gate` | `behavioral` |
| Approvals | `GET /api/v1/approvals`, `GET /api/v1/approvals/{id}`, `POST /api/v1/approvals/{id}/approve`, `POST /api/v1/approvals/{id}/reject`, `GET /api/v1/approvals/{id}/summary`, `POST /api/v1/approvals/{id}/summary/generate` | Approval screen | API: `tests/Feature/ApprovalAlphaPathTest.php` | Add no-success-artifact-on-failure assertion if missing | `php artisan test --group=alpha-gate` | `behavioral` |
| Awards | `POST /api/v1/awards`, `POST /api/v1/awards/{id}/signoff`, `POST /api/v1/awards/{id}/debrief/{vendorId}`, `GET /api/v1/awards/{id}/guidance`, `POST /api/v1/awards/{id}/guidance/generate` | Award/signoff/debrief screens | API: `tests/Feature/AwardWorkflowTest.php` | Mark gate tests; assert repeat signoff/debrief idempotence in grouped tests | `php artisan test --group=alpha-gate` | `behavioral` |
| Decision trail evidence | `GET /api/v1/decision-trail`, `GET /api/v1/decision-trail/{id}` | Decision trail panels | API: Multiple workflow tests | Add matrix references to exact workflow assertions after grouping | `php artisan test --group=alpha-gate` | `behavioral` |
| Projects/tasks WEB smoke check | Project/task list routes | Projects/tasks screens | WEB smoke/local only: `tests/projects-tasks-smoke.spec.ts` | Does not count as feature coverage; missing: `NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci -- tests/alpha-gate-real-api.spec.ts` for WEB real-API alpha journey evidence | `npm run test:e2e:ci -- tests/projects-tasks-smoke.spec.ts` | `smoke-only` |

## Deferred After Alpha Gate

| Route family | Reason | Follow-up expectation | Status |
|---|---|---|---|
| Account settings and payment methods | Not required to prove buyer procurement alpha journey | Add CRUD/validation/auth/tenant feature tests in post-alpha hardening | `deferred` |
| Reports schedules and exports | Alpha journey only needs dashboard/report summary evidence | Add schedule/export/download tests in post-alpha hardening | `deferred` |
| Integrations and API monitor | Operational surface, not buyer alpha path | Add external integration fake/error tests in post-alpha hardening | `deferred` |
| Handoffs | PO/contract handoff is not release-gate path for current alpha | Add workflow, retry, and external failure tests in post-alpha hardening | `deferred` |
| Notifications | Covered only where alpha jobs/mail matter | Add notification list/read/clear feature tests in post-alpha hardening | `deferred` |
| Evidence vault and generic documents | Alpha decision evidence is tested through workflows | Add upload/download/preview/storage cleanup tests in post-alpha hardening | `deferred` |
