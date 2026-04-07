# Atomy-Q Alpha Progress Analysis

Date: 2026-03-31
Scope: `apps/atomy-q/` (API + WEB + supporting apps/docs)

## Executive summary

Atomy-Q has moved beyond a skeleton. The repo already contains real JWT auth, tenant-scoped RFQ/list/detail paths, quote submission and normalization state, a real `POST /comparison-runs/final` flow, seeded demo data, and working UI shells for most of the product surface.

The gap to Alpha is not breadth anymore. It is the depth of the golden path:

- quote intake is not yet fully live end-to-end,
- awards/winner selection are still incomplete in the committed app surface,
- vendor master data is stubbed,
- comparison preview/matrix/readiness are now live and tenant-scoped, with lock/unlock/scoring-model deliberately deferred to beta,
- tenant/company lifecycle is not first-class,
- several identity/session/RBAC pieces are still no-op stubs,
- and production readiness is still held back by build, infra, and mock-fallback debt.

There is also active in-flight work in the workspace around quote lifecycle productionization, but that plan is not closed yet and the UI/backend pair is not fully aligned.

## What is already in good shape

- Auth exists and is tenant-bound through JWT.
- RFQ list/detail and line item flows are real.
- Normalization has persisted mapping/conflict/readiness data.
- Comparison finalization stores a real snapshot.
- Projects/tasks and several design-system primitives are already present.
- The frontend has a broad route surface and a substantial component library.
- The alpha audit and rollout plans already capture the intended critical path clearly.

## The 10 biggest Alpha gaps

1. **Winner selection / award flow is not fully productized**
   - Why it matters: Alpha requires a user to select a winner and save the result.
   - Evidence: the Alpha audit marks awards as unimplemented and the rollout plan puts awards in Week 3 as a critical dependency (`apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:34-35`, `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-INDEX.md:38-42`).
   - Current app signal: the award screen still reads from seed data and the finalize action is disabled in the committed WEB surface (`apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx:15-86`).
   - Result: the final step of the core quote lifecycle is still not reliable end-to-end.

2. **Vendor master data is still stubbed**
   - Why it matters: Alpha needs a trustworthy vendor surface, not empty placeholder responses.
   - Evidence: the vendor controller returns empty arrays and "Stub Vendor" responses (`apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorController.php:23-75`), and the audit calls `/vendors` stubbed (`apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:69-78`).
   - Result: integrations and UI screens that depend on vendor browse/history/performance are not production-grade.

3. **RFQ lifecycle mutations have now been closed in the live API**
   - Why it mattered: Alpha depends on duplicate/saveDraft/bulkAction working honestly and tenant-safely.
   - Resolution on 2026-04-03: `POST /rfqs/{id}/duplicate`, `PUT /rfqs/{id}/draft`, `POST /rfqs/bulk-action`, `PATCH /rfqs/{id}/status`, and `POST /rfqs/{id}/invitations/{invId}/remind` now delegate through the Nexus `SourcingOperations` lifecycle boundary and persist real tenant-scoped state.
   - Result: the RFQ management surface no longer depends on synthetic duplicate IDs, draft echo responses, or fake bulk-action counts.

4. **Quote ingestion and AI normalization are not complete**
   - Why it matters: the Alpha scope explicitly requires real quote intake and AI-assisted normalization.
   - Evidence: the audit says there is no wired LLM/quotation-intelligence pipeline, reparse is fake, and normalization reads were empty at audit time (`apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:31-35`, `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:98-109`).
   - Result: the most differentiated part of the product is still only partially real.
   - Deferred note: the persistence, tenant scoping, decision trail, and reparse mechanics have now been hardened, but the live LLM/model selection for normalization is intentionally deferred until the alpha-ready model decision is finalized. Revisit the quote-ingestion/AI-normalization gap-closure plan before any alpha declaration and close the remaining mock-backed extraction/mapping work only after the model choice is settled.

5. **Comparison preview/matrix/readiness workflow is now live**
   - Why it matters: Alpha users need to inspect comparison state before freezing and selecting a winner.
   - Resolution on 2026-04-06: `POST /comparison-runs/preview` now persists a real preview run and returns live matrix/readiness data, `GET /comparison-runs/{id}/matrix` and `GET /comparison-runs/{id}/readiness` serve the stored payloads, and the comparison detail page renders the live run state instead of a static vendor table.
   - Deferred note: scoring-model, lock, and unlock are intentionally beta-only controls and now return explicit deferred responses instead of fake state.
   - Result: gap 5 is closed for the alpha path.

6. **Tenant/company lifecycle is now being closed on the alpha branch**
   - Why it mattered: Atomy-Q is SaaS, so company/tenant creation and persistence needed to exist as real platform primitives.
   - Evidence from the audit: there was no `tenants` migration and no create-company flow, while the plan made a tenants table a Week 1 blocker (`apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:28-29`, `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-INDEX.md:38-39`).
   - Current branch state: `POST /api/v1/auth/register-company` now creates the tenant and first owner, the WEB app has a `/register-company` onboarding screen, and the alpha scope keeps full tenant administration deferred.
   - Result: the first-class company onboarding slice is now implemented; richer tenant admin remains a post-alpha concern.

7. **Identity, permissions, and session support are still stubbed**
   - Why it matters: Alpha needs a clear minimum security posture and predictable authorization behavior.
   - Evidence: the audit lists stub/no-op identity, session, permission, role, and audit components (`apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:12-13`, `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:69-79`).
   - Result: the app can authenticate, but the deeper authorization story is not yet a dependable product boundary.

8. **Production readiness is still blocked by build/test/environment debt**
   - Why it matters: Alpha cannot go out if the web build fails or the environment contract is ambiguous.
   - Evidence: the audit records a WEB build failure, missing AI env docs, queue-worker gaps, S3/MinIO dependency, and API/WEB port mismatch risk (`apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:122-123`, `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:163-187`).
   - Result: even if the product logic is close, deployment confidence is not yet there.

9. **Mock fallbacks still leak into live flows**
   - Why it matters: Alpha needs honest failures and live behavior, not silent seed fallbacks.
   - Evidence: the WEB docs still describe `NEXT_PUBLIC_USE_MOCKS` as a normal path for dashboard data, and the audit warns that many flows fall back to seed data when mocks are enabled (`apps/atomy-q/WEB/README.md:31-38`, `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:83-88`, `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md:122-129`).
   - Result: the app can pass local smoke tests while hiding live API regressions.

10. **The roadmap/docs are fragmented and some are stale**
    - Why it matters: alpha execution depends on a current, unambiguous plan.
    - Evidence: the core alpha audit/week plans were last updated on 2026-03-23, and the newer quote-lifecycle productionization plan was written on 2026-03-29 but appears to stop at Task 4 docs/visibility cleanup (`apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-4.md`, `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-5.md`, `docs/superpowers/plans/2026-03-29-quote-lifecycle-productionization.md`).
    - Result: the team has direction, but not a single closed execution ledger for what still blocks Alpha.

## Stalled or stale plan files

### Clearly stale

- `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-1.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-2.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-3.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-4.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-WEEK-5.md`

These were all introduced on 2026-03-23 and have not been updated since. They are useful as historical alpha intent, but they are no longer a reliable progress tracker by themselves.

### Active but incomplete

- `docs/superpowers/plans/2026-03-29-quote-lifecycle-productionization.md`

This remains the most relevant execution plan for the remaining quote lifecycle and award work. The comparison preview/matrix/readiness slice has been closed, but the plan is still useful for the beta-only controls, award polish, and doc follow-through.

- `docs/superpowers/plans/2026-04-04-quote-ingestion-ai-normalization-gap-closure-plan.md`

This plan is intentionally paused at the mock-backed extraction/mapping boundary. It should be reopened when the final LLM / AI normalization model is selected so the remaining mock wiring can be replaced with the production processor and mapper.

## Bottom line

If Alpha were declared today, the biggest risk would not be lack of screens. It would be that some paths still look complete while the backend returns stub, seed, or partially wired behavior outside the now-closed RFQ lifecycle mutation gap.

The repo is close on structure and far from done on honesty. The next phase should focus on:

- closing the remaining quote lifecycle and award work end-to-end,
- removing or gating stubs on the golden path,
- making tenant/company creation first-class,
- and re-verifying build, queue, storage, and live-mode smoke behavior before any Alpha claim.
