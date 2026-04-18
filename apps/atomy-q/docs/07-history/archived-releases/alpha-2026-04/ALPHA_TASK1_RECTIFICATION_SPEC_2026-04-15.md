# Alpha Task 1 Rectification Spec

**Date:** 2026-04-15  
**Source:** Task 1 release evidence baseline in `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`  
**Status:** Ready for implementation planning

## 1. Purpose

Task 1 established the alpha release evidence baseline and exposed several gaps that must be rectified before Atomy-Q can move to downstream alpha tasks with reliable release evidence. This spec defines the intended behavior for those rectifications so the team fixes the release blockers rather than only adjusting tests.

The goal is to make the baseline gates green while preserving the alpha scope: tenant registration/login, RFQ creation, quote upload and ingestion, normalization, comparison finalization, award creation/signoff, vendor follow-up, project ACL, operational approvals, and minimal identity operations.

## 2. Current Gaps

| Area | Gate | Observed failure | Required outcome |
|---|---|---|---|
| WEB live API helper | `npm run build` | TypeScript rejects direct conversion of `Error` to `Record<string, unknown>` in `src/lib/api-live.ts`. | Build passes with a type-safe error decoration pattern. |
| WEB award page | `npm run test:unit` | Award empty-state test expects award creation UI, but the page only renders passive empty state text. | No-award state offers a clear Create Award path when a final frozen comparison run exists. |
| API MFA verify | API alpha matrix and full suite | `POST /auth/mfa/verify` returns 422 unless the client submits `tenant_id`. | MFA verify resolves tenant context from the challenge and accepts the login response contract already used by the test/client. |
| API quote upload | API alpha matrix and full suite | Upload test receives `failed` instead of `ready` because the test RFQ has no line items for the mock ingestion processor to extract. | A valid quote-upload fixture produces ready status with zero blocking issues; malformed/no-line RFQs remain a controlled failure path. |
| API model relations | API full suite | `Award::creator()` is missing while relation coverage expects it. | Award exposes the expected creator relation without changing signoff semantics. |
| API sourcing adapter tests | API full suite | In-memory `rfqs` schema omits `project_id`, so duplicate RFQ adapter tests fail before reaching the behavior under test. | Test schema mirrors current RFQ persistence columns needed by the adapter. |

## 3. Rectification Strategy

Use the smallest production-aligned fixes that restore release evidence. The rectification should not hide failures by weakening assertions, disabling tests, or adding mock-only branches that differ from live behavior.

For WEB, the changes should make strict TypeScript and unit tests reflect intended user behavior. The live API helper should continue to throw rich errors in live mode. The award page should use the already-existing comparison-run and vendor hooks to present a creation action only when there is enough data to create an award.

For API, fixes should respect tenant isolation and the current three-layer boundary expectations. The MFA verify endpoint should not require the browser to echo tenant context after login has already created a tenant-scoped challenge. Quote upload should use a valid ingestion fixture to prove the happy path, while preserving a deterministic failure path for invalid input. Full-suite failures caused by model/schema drift should be corrected in the model or test fixture rather than ignored.

## 4. Intended Behavior

### 4.1 WEB Live API Error Decoration

`fetchLiveOrFail` must preserve response status and response payload on thrown errors without unsafe TypeScript casts. The implementation may introduce a narrow local type such as `Error & { status?: number; response?: unknown }` and assign through that type.

Completion means `npm run build` no longer fails at `src/lib/api-live.ts`, and error consumers still have access to `status` and `response` when Axios provides them.

### 4.2 WEB Award Creation Empty State

When an RFQ has no award record and at least one final frozen comparison run exists, the award page must show concise creation guidance and a `Create Award` action. The guidance should explain that the user must select a vendor based on the final comparison run. The action should call the existing award creation mutation and should be disabled while pending or when mock mode is enabled.

If no final frozen comparison run exists, the page should keep the current guidance that the user must freeze a comparison run before creating an award. The page must not fabricate award data in live mode.

### 4.3 Award Creation Data Source

The award creation UI should derive candidate vendors from the RFQ vendor roster first and may fall back to vendor IDs in the final comparison snapshot if the roster is unavailable. The payload sent to the API must include the RFQ ID, selected final comparison run ID, and selected vendor ID. The page should keep existing signoff and debrief behavior after an award exists.

### 4.4 MFA Verification Contract

`POST /api/v1/auth/mfa/verify` must accept `challenge_id` and `otp` as required fields. `tenant_id` may be accepted as optional compatibility input, but the server must resolve the authoritative tenant from the stored MFA challenge.

If a supplied `tenant_id` does not match the stored challenge tenant, the endpoint must return the same generic invalid-challenge response used for invalid or expired challenges. It must not reveal whether the challenge exists in another tenant. All challenge consume/increment operations must continue to use the stored tenant ID.

### 4.5 Quote Upload Ingestion Fixture

The quote upload happy-path test must create an RFQ with at least one line item because the current mock content processor intentionally extracts lines from RFQ line items, not from arbitrary text file content. With a valid line-item fixture, synchronous testing-mode processing should produce normalized source lines, zero blocking issues, and final `ready` status.

A separate negative assertion should remain or be added to show that an upload without extractable RFQ line context does not masquerade as ready. That protects the alpha flow from false readiness.

### 4.6 Award Creator Relation

`Award` should expose the relation expected by model relation coverage. Because the current awards table stores `signed_off_by` rather than `created_by`, `creator()` should map to the user who signed off the award unless the schema is later extended with a distinct creator column. This keeps the relation compatible with the current schema and does not alter the existing `signedOffByUser()` relation.

If product later needs separate award creator versus signoff actor semantics, that belongs in a post-alpha schema change and OpenAPI/client update, not this rectification pass.

### 4.7 Sourcing Adapter Test Schema Drift

`SourcingOperationsAdaptersTest` must keep its in-memory RFQ schema aligned with the adapter fields it exercises. The schema must include nullable `project_id` because `AtomyRfqLifecyclePersist::createDuplicate()` copies that field from the source RFQ.

The duplicate-number tests must then reach their intended assertions: highest numeric suffix selection and domain exception wrapping after persistent duplicate-number failures.

## 5. Non-Goals

- Do not broaden alpha scope beyond the Task 1 failures.
- Do not replace the quote intelligence mock binding; that remains Task 2.
- Do not regenerate the OpenAPI contract unless a rectification changes API shape beyond validation optionality.
- Do not archive or move the active alpha plan or checklist.
- Do not add synthetic live-mode fallbacks in WEB hooks.

## 6. Acceptance Criteria

- `cd apps/atomy-q/WEB && npm run build` passes.
- `cd apps/atomy-q/WEB && npm run test:unit` passes.
- `cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest"` passes.
- `cd apps/atomy-q/API && php artisan test` passes with zero failures.
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` is updated with new evidence after the rectification is implemented.

## 7. Risk Acceptance / Exceptions

No tolerated exceptions are accepted for Task 1 rectification at spec time.

If a future execution of `cd apps/atomy-q/API && php artisan test` encounters an unrelated failure that the release owner wants to tolerate, it must be documented here with:

- exact failing command and test/class name
- rationale for treating it as unrelated to Task 1 rectification
- owner responsible for follow-up
- mitigation plan and expected resolution timing
