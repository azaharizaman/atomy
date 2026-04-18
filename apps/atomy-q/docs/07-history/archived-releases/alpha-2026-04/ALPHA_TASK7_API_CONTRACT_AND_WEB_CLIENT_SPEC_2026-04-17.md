# Alpha Task 7 API Contract And WEB Client Spec

## Document Control

- **Task:** Section 9, Task 7 - Regenerate API Contract And WEB Client
- **Date:** 2026-04-17
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 7 closes blocker `A6` by making the committed OpenAPI document and the generated WEB client trustworthy again for the design-partner alpha path.

The Atomy-Q alpha has already shipped meaningful backend and frontend changes across quote intelligence, award lifecycle, live-mode fail-loud behavior, alpha-surface gating, and minimal Users & Roles. That progress is valuable only if the WEB is consuming request and response contracts that actually match the Laravel API. Recent follow-up work already exposed one concrete example: `POST /users/invite` had drifted badly enough that the generated client emitted `body?: never`, forcing a local cast until the contract was corrected.

This spec defines a contract-first alignment pass for the alpha-critical route families. It does not broaden product scope. It restores the source-of-truth chain so Task 8 staging readiness and Task 9 release gating can rely on committed artifacts instead of stale generated code and local workarounds.

## 2. Current State

The repository already has the contract toolchain in place:

- `apps/atomy-q/API/config/scramble.php` defines OpenAPI generation through Scramble.
- `apps/atomy-q/openapi/openapi.json` is the committed export consumed by the WEB.
- `apps/atomy-q/WEB/package.json` exposes `npm run generate:api`.
- `apps/atomy-q/WEB/src/generated/api/**` is committed and used by hand-written hooks.

The remaining problem is not the absence of code generation. The problem is contract drift between the real API behavior and the committed generated client.

Observed current-state risks:

- Some alpha-critical hooks still normalize around generated drift instead of consuming a fully accurate generated contract.
- Recent users/invite work proved that stale OpenAPI can emit incorrect generated request types (`body?: never`) and incomplete response shapes for live routes.
- The WEB still uses hand-written hooks as the compatibility layer for UI-facing data models, which is acceptable, but those hooks must not hide stale request or response contracts through unsafe casts or fake local types.
- Staging readiness depends on the committed generated client being current. Task 8 should not discover alpha-critical schema drift for the first time.

## 3. Scope

### In scope

- Export the latest OpenAPI document from the current Laravel API.
- Regenerate the committed WEB client from that export.
- Audit and fix contract drift for alpha-critical route families only.
- Remove unsafe generated-client escape hatches where the regenerated contract now supports the real runtime shape.
- Keep hand-written hooks for payload normalization and UI-friendly shaping, but make them consume honest generated request and response types.
- Record Task 7 evidence and any accepted remaining wrapper exceptions in `ALPHA_RELEASE_CHECKLIST.md`.

### Out of scope

- A repo-wide OpenAPI cleanup for every route in the platform.
- Migrating all WEB data access to generated-client purity.
- Expanding deferred or hidden alpha surfaces into live product scope.
- Introducing new alpha functionality that is not already part of the release plan.
- Refactoring unrelated hooks or controllers just because regeneration touches their generated types.

## 4. Alpha-Critical Boundary

Task 7 is intentionally limited to the alpha-critical route families:

- auth
- users and roles
- RFQs
- quote submissions
- normalization
- comparison runs
- awards
- vendors
- decision trail

These are the routes that matter to the current design-partner path or to the minimal tenant-admin surface accepted into alpha.

### Boundary rule

Task 7 must regenerate the entire client, because generation works at repository scope. However, remediation is required only for:

1. alpha-critical routes listed above
2. non-alpha drift that blocks generation
3. non-alpha drift that blocks `npm run build`
4. non-alpha drift in shared generated-client code that breaks alpha-critical usage

All other non-alpha drift may be explicitly deferred and documented rather than fixed in this task.

## 5. Architecture

Task 7 preserves one contract chain:

1. Laravel controllers and request/response documentation are the runtime source of truth.
2. Scramble-exported `apps/atomy-q/openapi/openapi.json` is the committed interchange artifact.
3. `apps/atomy-q/WEB/src/generated/api/**` is the generated client consumed by alpha hooks.
4. Hand-written WEB hooks remain the UI-facing compatibility layer for normalization, not for hiding stale schema definitions.

### Architectural rule

If an alpha-critical generated type is wrong, the default fix path is:

- correct the API schema or export source
- regenerate the committed client
- then simplify or correct the consuming hook

The default fix path is **not**:

- leave the schema wrong
- patch around it with `as never`
- hide the mismatch behind local request or response types

## 6. Contract Alignment Rules

Every alpha-critical contract issue discovered during Task 7 must follow this sequence:

1. Run `php artisan scramble:export --path=../openapi/openapi.json`.
2. Run `npm run generate:api`.
3. Inspect the generated diff for the affected route family.
4. Decide whether the generated shape now matches the real API behavior.
5. If it does, remove the local workaround from the consuming hook.
6. If it does not, fix the API documentation or response schema source, then regenerate again.
7. Re-run targeted verification for the affected alpha-critical slice.

### Allowed hook behavior

Hand-written hooks may still:

- rename API fields into UI-oriented names
- normalize nullability
- reject malformed live payloads loudly
- convert raw envelope shapes into page-friendly models

Hand-written hooks may not:

- invent request bodies that the generated client says do not exist
- use unsafe casts to hide alpha-critical generated contract errors
- swallow non-2xx server error envelopes and replace them with misleading parse errors

## 7. Drift Categories

Task 7 should distinguish contract drift into three categories.

### 7.1 Required remediation drift

This must be fixed now:

- missing or wrong request bodies for alpha-critical routes
- incomplete or wrong response shapes for alpha-critical routes
- generated operation signatures that force unsafe casts for alpha-critical routes
- generated error behavior that causes alpha-critical hooks to misreport API failures

### 7.2 Build-blocking collateral drift

This is outside the main task boundary, but still must be fixed if it blocks:

- OpenAPI export
- client generation
- WEB build
- shared generated code used by alpha-critical slices

### 7.3 Deferred non-alpha drift

This may remain open only if:

- it is outside the alpha-critical boundary
- it does not break generation
- it does not break build
- it does not affect shared generated-client behavior consumed by alpha-critical hooks

Deferred drift must be recorded, not ignored silently.

## 8. Required Route Families And Consumers

Task 7 must explicitly audit the generated contract and WEB consumers for the following alpha-critical slices.

### 8.1 Users and roles

Minimum consumer focus:

- `apps/atomy-q/WEB/src/hooks/use-users.ts`
- `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`

Required outcomes:

- invite request body matches the real API contract
- generated user invite response shape is sufficient for the live page
- non-2xx envelopes surface real server error messages

### 8.2 Awards

Minimum consumer focus:

- `apps/atomy-q/WEB/src/hooks/use-award.ts`

Required outcomes:

- create, read, debrief, and signoff operations are generated with the request and response shapes the hook actually consumes
- any hook-local compatibility mapping is limited to UI shaping, not stale contract hiding

### 8.3 Vendors

Minimum consumer focus:

- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`

Required outcomes:

- live vendor list and invitation-related data consumed by the alpha RFQ path match the exported contract

### 8.4 Normalization and comparison

Minimum consumer focus:

- `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`

Required outcomes:

- generated request and response shapes reflect the live fail-loud alpha path
- hooks keep their payload validation, but do not compensate for stale generated route definitions

### 8.5 RFQ, quote submissions, auth, and decision trail

These route families must still be audited during generation review even if no hook change is required.

Task 7 is incomplete if the generated diff reveals alpha-critical drift in these families and that drift is neither fixed nor explicitly dispositioned.

## 9. Documentation Requirements

Task 7 must update documentation where evidence or accepted exceptions change.

Required documentation targets:

- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` if WEB behavior or hook expectations change
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` if API export or schema behavior changes

The checklist entry for Task 7 must record:

- the export command used
- the generation command used
- the build result
- the targeted verification result
- any explicitly accepted remaining wrapper exceptions

## 10. Acceptance Criteria

Task 7 is complete only when all of the following are true:

1. `apps/atomy-q/openapi/openapi.json` is freshly exported from the current Laravel API.
2. `apps/atomy-q/WEB/src/generated/api/**` is regenerated from that export and committed.
3. Alpha-critical request and response contracts match the current runtime behavior for auth, users and roles, RFQs, quote submissions, normalization, comparison runs, awards, vendors, and decision trail.
4. Unsafe generated-client escape hatches are removed for alpha-critical routes where the regenerated client now supports the real shape.
5. Any remaining alpha-critical wrapper exception is individually named, justified, and recorded in `ALPHA_RELEASE_CHECKLIST.md`.
6. No non-alpha drift is left unresolved if it blocks generation, build, or shared alpha-critical client correctness.
7. The WEB build succeeds against the regenerated client.

## 11. Verification

Minimum required verification for Task 7:

- `cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json`
- `cd apps/atomy-q/WEB && npm run generate:api`
- `cd apps/atomy-q/WEB && npm run build`
- `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts src/hooks/use-users.test.tsx src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-comparison-run.live.test.ts src/hooks/use-comparison-run-matrix.live.test.ts src/hooks/use-normalization-review.live.test.ts`

If regeneration changes additional alpha-critical slices, run the relevant focused tests for those slices and record them in the checklist.

## 12. Risks And Constraints

### Risks accepted in this task

- Generation may reveal broader non-alpha drift than expected; Task 7 may touch a small amount of collateral code if generation or build cannot proceed otherwise.
- Some hand-written hooks may still remain after Task 7 because UI-facing normalization is still the chosen frontend pattern.

### Constraints

- Task 7 must not sprawl into Task 8 staging-readiness work.
- Task 7 must not reopen Task 1 to Task 6 scope decisions.
- Task 7 must prefer fixing the contract source over adding new local type workarounds.

## 13. Non-Goals

- This task does not promise that every route in `apps/atomy-q/API/routes/api.php` will have perfect generated contract parity.
- This task does not require removal of all hand-written hook normalization code.
- This task does not require a repo-wide replacement of manual API clients outside the alpha-critical path.
- This task does not change the accepted alpha product surface.
