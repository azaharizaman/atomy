# Atomy-Q Alpha Release Review Pass

**Date:** 2026-04-17  
**Scope:** Task 4 live-mode fail-loud slice plus release-plan fit review for the Atomy-Q WEB alpha golden path.  
**Primary references:**  
- `apps/atomy-q/docs/ALPHA_TASK4_LIVE_MODE_FAIL_LOUD_SPEC_2026-04-17.md`
- `apps/atomy-q/docs/ALPHA_TASK4_LIVE_MODE_FAIL_LOUD_IMPLEMENTATION_PLAN_2026-04-17.md`
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`

## Final Assessment

Task 4 is fit for the alpha release plan and satisfies the stated spec boundary.

The current implementation now enforces the intended live/mock split on the RFQ golden path:

- live mode does not silently fall back to seed or fabricated business data
- `undefined` live responses fail loudly
- malformed live payloads fail loudly through hook normalizers
- page-level consumers render explicit unavailable states instead of misleading empty-success states
- mock mode remains explicit and isolated behind `NEXT_PUBLIC_USE_MOCKS === 'true'`

No blocking correctness issues were found in the Task 4 slice during this pass.

## Review Findings

### Open findings

None within the Task 4 spec boundary.

### Residual risk

- This pass confirms WEB-side fail-loud behavior only. It does not change or newly verify Laravel-side quote-processing semantics beyond the existing alpha release checklist evidence.
- Several CodeRabbit comments were style or wider architecture preferences rather than Task 4 blockers. Those were intentionally not expanded into follow-on work to avoid scope creep.

## CodeRabbit Review Triage

Command run:

```bash
cr review --agent --type uncommitted
```

Result:

- Review completed with 26 findings.
- Applied only the findings that improved Task 4 correctness, fail-loud clarity, test precision, or release documentation.

### Applied

- Clarified readiness payload errors in `use-comparison-run-readiness.ts` so invalid `blockers` and `warnings` explicitly report the expected array contract.
- Tightened readiness live-test assertions to verify the exact malformed-payload failure message.
- Updated live-test descriptions in:
  - `apps/atomy-q/WEB/src/hooks/use-rfqs.live.test.ts`
  - `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.live.test.ts`
  - `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts`
- Tightened unavailable-state assertions in:
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx`
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`
- Consolidated duplicate Task 4 entries in `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`.
- Clarified the Blocker A3 closure note in `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`.

### Deferred as not applicable or out of scope

- Hook refactors that only changed `enabled` wiring in mock mode without changing live-mode correctness.
- Readability-only refactors such as status lookup helpers.
- Extra assertion additions that did not materially improve Task 4 fail-loud coverage.
- Normalization cleanup suggestions unrelated to the fail-loud release rule.
- Broader mock-mode query restructuring beyond the implementation-plan boundary.

## Repo-Wide Review Pass

Checks performed:

- Manual diff audit against the Task 4 spec and implementation plan
- Release-plan fit check against Section 9 Task 4 of `ALPHA_RELEASE_PLAN_2026-04-15.md`
- `git diff --check`
- CodeRabbit review triage

Repo-wide review result:

- No whitespace or patch-format issues were found by `git diff --check`.
- No newly identified blockers were found outside the already intended Task 4 file set.
- The modified files remain tightly aligned to the live-mode fail-loud goal and do not introduce obvious scope creep into unrelated alpha tasks.

## Verification Evidence

```bash
cd apps/atomy-q/WEB && npx vitest run \
  'src/hooks/use-rfq.live.test.ts' \
  'src/hooks/use-rfqs.live.test.ts' \
  'src/hooks/use-rfq-vendors.live.test.ts' \
  'src/hooks/use-quote-submissions.live.test.ts' \
  'src/hooks/use-normalization-source-lines.live.test.ts' \
  'src/hooks/use-normalization-review.live.test.ts' \
  'src/hooks/use-comparison-runs.live.test.ts' \
  'src/hooks/use-comparison-run.live.test.ts' \
  'src/hooks/use-comparison-run-matrix.live.test.ts' \
  'src/hooks/use-comparison-run-readiness.live.test.ts' \
  'src/hooks/use-award.live.test.ts' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx'
```

- PASS. 16 files, 76 tests.

```bash
cd apps/atomy-q/WEB && npm run build
```

- PASS.

## Ship Decision

Proceed with commit, push, and PR creation for the Task 4 branch.
