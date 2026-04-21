# Implementation Summary

## Implementation Status

| Feature | Status | Notes |
| :--- | :--- | :--- |
| **Project Setup** | ✅ | Next.js 16, Tailwind, TypeScript initialized. |
| **Authentication** | ✅ | JWT Auth with refresh token flow, Zustand store, redesigned login, plus forgot/reset password flows. |
| **Company onboarding** | ✅ | Public `register-company` flow creates the first tenant + owner user, boots the auth session, and links from the login screen. |
| **API Client** | ⚠️ | Axios instance in `src/lib/api.ts`; OpenAPI spec at `../openapi/openapi.json` with `npm run generate:api` → `src/generated/api` (@hey-api/client-fetch). Hooks still use manual types until migrated. |
| **API errors** | ✅ | `src/lib/api-error.ts` normalizes Laravel `errors` / `details` / `message`; login uses `parseApiError`. |
| **Dashboard** | ✅ | Implemented Screen-Blueprint style dashboard (pipeline stats, savings, activity, approvals, category breakdown, SLA alerts, quick actions). |
| **UI Components** | ✅ | Ported Design-System-v2 primitives into `src/components/ds/*` (Badge, Button, Input, FilterBar/PageHeader, DataTable, Card). |
| **Default Layout (Blueprint)** | ✅ | Sidebar structure + TopBar actions aligned to Screen Blueprint; RFQ sub-nav badges from `GET /rfqs/counts` when live API is enabled. |
| **RFQ List (Blueprint Screen 1)** | ✅ | `/rfqs` uses API pagination meta (`useRfqs` → `{ items, meta }`), filters, bulk selection, expandable rows, and workspace navigation. |
| **RFQ bulk toolbar scope** | ✅ | Live mode now only exposes backend-supported RFQ bulk actions (`Close Selected`, `Cancel Selected`); broader mock-only actions remain available when `NEXT_PUBLIC_USE_MOCKS=true`. |
| **Workspace Layout (Blueprint Screen 2 frame)** | ✅ | `/rfqs/[rfqId]/*` uses collapsed rail + Active Record Menu + work surface. |
| **RFQ Workspace Overview (Blueprint Screen 2)** | ✅ | `/rfqs/[rfqId]/overview` with KPI scorecards; **Schedule** uses `HorizontalProcessTrack` (date = title, event = subtitle, equal-width columns, no horizontal scroll); live mode loads activity via `GET .../activity` merged into overview hook. |
| **E2E Testing** | ✅ | Playwright coverage is split by ownership: alpha shell/auth (`tests/auth.spec.ts`, `tests/dashboard-nav.spec.ts`, `tests/smoke.spec.ts`), RFQ alpha journeys (`tests/rfq-alpha-journeys.spec.ts`), adjacent projects/tasks smoke (`tests/projects-tasks-smoke.spec.ts`), adjacent settings/users smoke (`tests/settings-users-smoke.spec.ts`), and narrow live RFQ create/overview smoke (`tests/rfq-lifecycle-e2e.spec.ts`). |
| **Routing** | ✅ | Added not-found page for undefined routes with design-system styling. |
| **RFQ Management** | 🚧 | RFQ List + Workspace Overview done; other workspace sections are scaffolded via `/rfqs/[rfqId]/[section]`. **`/rfqs/new` and RFQ Details require `submission_deadline`** (aligned with API NOT NULL). |
| **Vendor Management** | ❌ | Pending implementation. |
| **Quote Intake** | ✅ | Quote list, detail, normalize, comparison freeze, comparison-run detail, award, and approvals screens now consume live API data when `NEXT_PUBLIC_USE_MOCKS=false`. Live quote rows preserve nullable fields, normalize confidence/blocks strictly, reject empty-string unit prices, scope normalization source lines to the active `quoteId`, and expose accessible controlled selection controls on normalization rows. Award debriefs now use user-entered draft text instead of a hard-coded template message. Mock/demo branches remain available for local seed mode. |
| **Comparison Runs** | ✅ | `/rfqs/[rfqId]/comparison-runs` and the run detail page render live persisted run, matrix, readiness, and snapshot payloads; the snapshot banner only appears for real final runs and the retired lock/unlock controls are no longer shown in the alpha UI. |
| **Approvals** | 🚧 | Global queue `/approvals` + detail `/approvals/[id]` call API; RFQ-scoped approval URLs now use the live pending-approval list, forward the RFQ filter to the hook, and normalize backend priority values before rendering. |
| **Turbopack Root Config** | ✅ | Resolve `tailwindcss` from WEB config dir to avoid parent-root module resolution. |

## Next Steps
1.  Replace mocked RFQ list/detail data with real payload mapping (see `BACKEND_API_GAPS.md` for required fields/params).
2.  Continue refining quote intake, comparison, award, and approval screens as the remaining beta-only controls are productized.
3.  Implement Vendor Management and Invitation lifecycle on top of the now-live quote flow.
4.  Expand Playwright coverage per workflow slice.

## 2026-03-19 PR Remediation
- Project ACL editor now uses stable draft-local row IDs to prevent React remounting when `userId` is edited.
- Project ACL save flow now blocks empty ACL submissions both in button-disabled state and at click handler guard.
- `use-update-project-acl` now validates mapped roles at runtime against canonical allowed roles and fails fast on invalid payloads.
- Seed data now includes realistic enterprise RFQ presets (software renewal, SOC services, WAN upgrade, facilities maintenance, HRIS partner) in addition to generated lifecycle data.

## 2026-04-15 PR Review Remediation

- Aligned seeded project IDs with the RFQ generator project ID pool (`01J...`) so project-linked seed lookups no longer miss.
- Removed duplicate `fetchLiveOrFail` import in `use-award.ts`.
- Stopped fabricated approval pending-count values (`2`) and propagated unavailable live data as `undefined` instead.
- Removed live-path seed fallbacks in approvals list/detail and RFQ overview hooks; seed logic now runs only in explicit mock mode.
- Removed duplicate readiness fetch + mock fallback in `use-comparison-run-readiness.ts`; live unavailability now surfaces as an error.
- Removed duplicate fetch retry in `use-comparison-run.ts`; in mock mode undefined responses now fall back directly to the mock builder, while live mode keeps fail-loud behavior and does not fallback on undefined/error responses.
- Added seeded fallbacks for mock mode in quote submissions and RFQ invitations so normalizers always receive valid payloads.
- Corrected `FetchResponseType` to use Axios-compatible `'arraybuffer'`.
- Cleaned unused imports and fixed assertion indentation consistency in comparison run test files.

## 2026-04-15 Alpha Task 1 Rectification

- `fetchLiveOrFail` now decorates live API failures through a typed `LiveApiError` shape, preserving status/response metadata without unsafe `Error` casts.
- The RFQ award page now exposes a live create-award path when no award exists and a final comparison run is available, using real comparison-run and RFQ-vendor hook data. Mock mode still disables the mutation.
- `useAward` now returns a `store` mutation for `POST /awards` and invalidates the RFQ award query after creation.
- Verification: `npm run lint` exits 0 with the documented existing warnings; `npm run build` and `npm run test:unit` pass.

## 2026-04-16 Alpha Task 3 Award End-To-End

- `use-award.ts` now derives live create-award payloads from finalized comparison snapshot and matrix evidence, rejects malformed live award envelopes/rows, and requires complete vendor pricing coverage plus resolved currency metadata.
- `use-comparison-runs.ts` now rejects malformed live comparison-run envelopes and rows instead of silently defaulting missing type/date fields.
- The RFQ award page now surfaces explicit live-mode load, create, and signoff errors, restricts award candidates to vendors backed by complete final-run evidence, and keeps the existing-award view independent from final-run detail reloads.
- `rfq-lifecycle-e2e.spec.ts` now boots a persisted live-mode auth session, stubs explicit RFQ/comparison/approval/award routes, and executes the single-winner alpha award path end to end: create award, send debrief for the losing vendor, and finalize signoff on the award screen.
- Verification:
  - `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts` -> PASS, 13 tests.
  - `cd apps/atomy-q/WEB && npx vitest run 'src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx'` -> PASS, 8 tests.
  - `cd apps/atomy-q/WEB && PLAYWRIGHT_USE_EXISTING_SERVER=1 PLAYWRIGHT_BASE_URL=http://localhost:3100 NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts` -> PASS, 1 passed / 1 skipped.

## 2026-04-17 Alpha Task 4 Live-Mode Fail-Loud

- Golden-path hooks now keep seed access inside explicit `NEXT_PUBLIC_USE_MOCKS === 'true'` branches only, and never return seed-backed or fabricated business data in live mode.
- Live-mode golden-path hooks now fail loudly instead of falling back to seed or fabricated success states when the API returns `undefined` or malformed payloads.
- In live mode, `undefined` payloads now throw explicit unavailable errors across RFQ detail, vendor invitations, quote submissions, normalization review, comparison runs, comparison detail, comparison matrix, comparison readiness, and award reads.
- Hook normalizers reject malformed live payloads (missing required fields, wrong envelope types, wrong primitives for required fields) so broken contracts surface as React Query errors instead of silent empty states.
- RFQ vendors, quote intake, normalize, comparison runs, and award pages now render explicit unavailable/recovery states when live dependencies error instead of collapsing to empty-state success.
- Added and extended `.live.test.ts` coverage for RFQ detail, RFQs list, vendor invitations, quote submissions, normalization source lines, normalization review, comparison runs, comparison detail, comparison matrix, comparison readiness, and awards.
- Verification:
  - `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-rfq.live.test.ts src/hooks/use-rfqs.live.test.ts src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-quote-submissions.live.test.ts src/hooks/use-normalization-source-lines.live.test.ts src/hooks/use-normalization-review.live.test.ts src/hooks/use-comparison-runs.live.test.ts src/hooks/use-comparison-run.live.test.ts src/hooks/use-comparison-run-matrix.live.test.ts src/hooks/use-comparison-run-readiness.live.test.ts src/hooks/use-award.live.test.ts` -> PASS, 57 tests.
  - `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx` -> PASS, 19 tests.

## 2026-04-19 Task 3 Line Items Inline Drawer

- The line-items screen now has an "Add line item" CTA button in the header (visible for draft RFQs).
- The empty state (both table and grid view) now has an inline "Add line item" action button.
- Clicking any CTA opens the `<LineItemDrawer>` inline drawer.
- The drawer uses the live `useCreateRfqLineItem` mutation and invalidates the line-items query on successful creation.
- Mock mode blocks persistence with an explicit read-only message.
- Verification:
   - `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx src/hooks/use-create-rfq-line-item.test.ts` -> PASS, 6 tests.
   - `cd apps/atomy-q/WEB && npm run build` -> PASS.
   - `cd apps/atomy-q/WEB && npm run lint` -> PASS (existing warnings).

## 2026-04-17 Alpha Task 5 Hide Or Defer Non-Alpha Surfaces

- Added `NEXT_PUBLIC_ALPHA_MODE`-driven alpha policy helpers in `src/lib/alpha-mode.ts` for top-level nav visibility, RFQ workspace visibility, and deferred-route detection.
- Introduced the shared `AlphaDeferredScreen` component with the exact deferred copy `This feature will be available in future releases`.
- Hid `Documents`, `Reporting`, `Approval Queue`, `Settings`, `Projects`, and `Task Inbox` from the alpha dashboard shell while keeping RFQ-scoped approvals visible.
- Hid `Documents`, `Reporting`, and `Settings` from the RFQ workspace collapsed rail in alpha mode.
- Added alpha gating to representative hidden pages so direct access renders the shared deferred screen instead of placeholder content.
- Narrowed `tests/screen-smoke.spec.ts` to the alpha-visible dashboard and requisition/RFQ list surface, and added `src/app/(dashboard)/deferred-routes.test.tsx` for representative hidden-route coverage.
- Verification:
  - `cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx src/app/(dashboard)/deferred-routes.test.tsx` -> PASS, 2 files, 9 tests.
  - `cd apps/atomy-q/WEB && npx eslint src/lib/alpha-mode.ts src/components/alpha/alpha-deferred-screen.tsx src/components/alpha/alpha-deferred-screen.test.tsx src/config/nav.ts 'src/app/(dashboard)/layout.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/layout.tsx' src/components/workspace/active-record-menu.tsx tests/dashboard-nav.spec.ts 'src/app/(dashboard)/documents/page.tsx' 'src/app/(dashboard)/reporting/page.tsx' 'src/app/(dashboard)/settings/page.tsx' 'src/app/(dashboard)/settings/users/page.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx' 'src/app/(dashboard)/deferred-routes.test.tsx' tests/screen-smoke.spec.ts` -> PASS.
  - `cd apps/atomy-q/WEB && NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/dashboard-nav.spec.ts` -> PASS, 1 test.
  - `cd apps/atomy-q/WEB && NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/screen-smoke.spec.ts` -> PASS, 1 test.

## 2026-04-17 Alpha Task 6 Minimal Users & Roles

- The Settings > Users & Roles page now consumes live `use-users` data for the tenant user directory, shows explicit loading / empty / error states, and supports invite, suspend, and reactivate actions from the live API.
- The users page keeps mutation feedback in-page and renders the live heading `Users & Roles`, so nav and smoke tests can validate the productionized admin surface without adding CRUD-heavy coverage.
- Follow-up review remediation kept Task 6 within spec boundaries: the invite form now requires a display name, sends only `{ email, name }`, and documents that baseline role assignment stays fixed during alpha instead of exposing unsupported role selection.
- `use-users.ts` now rejects null single-user envelopes explicitly and requires `is_system_role` to be a real boolean so malformed live payloads fail loudly instead of coercing string values.
- `use-users.ts` now throws non-2xx client-fetch envelopes from the server error payload before normalizing, validates users pagination meta as finite integers, and the page-level `role="alert"` banner now appears above the users table so suspend/reactivate failures are visible.
- The invite OpenAPI contract was regenerated so the generated `userInvite` helper accepts the required `{ email, name }` body instead of relying on the old `as never` cast.
- Users-page tests now cover the required `Name` field and pending-activation rows without action buttons.

## 2026-04-17 Alpha Task 2 Roles Contract Parity Follow-Up

- Regenerated `/roles` client types now expose the concrete role item shape, so `use-users.ts` consumes `UserRolesResponse['data']` directly instead of normalizing from an `unknown[]` contract.
- `use-users.test.tsx` no longer carries the stale `as never` workaround for the undefined invite-response guard case.

## 2026-04-18 Global Error Fallback

- Added `src/app/global-error.tsx` as the root App Router error boundary for unexpected failures outside the normal page-level error states.
- The fallback now shows a user-facing recovery path with retry and dashboard actions, plus a support-oriented panel that links to `support@atomy-q.nexusnv.net`.
- When Next provides an error digest, the screen surfaces it as a reference code for support without exposing stack traces or internal details.

## 2026-04-18 RFQ Line Items Live Source

- Replaced the requisition line-items page's canned mock rows with a live `useRfqLineItems` query sourced from `GET /rfqs/{rfqId}/line-items`.
- New requisitions now render the empty state when no line items exist, instead of showing unrelated seeded hardware rows.
- The line-items page keeps the existing table/grid presentation, but the data now reflects the active requisition context.
- Added `use-rfq-line-items.live.test.ts` with the same live-mode coverage pattern used by the other hooks: transport error, valid payload, undefined payload, and malformed row rejection.
- Added `use-rfq-line-items.test.ts` to lock the current mock-mode behavior to an empty list until mock seed line-item data is introduced.

## 2026-04-18 RFQ Line Items Seed Enrichment

- Added deterministic RFQ line-item seed data to `src/data/seed.ts` so mock mode can render real requisition content.
- Introduced `getSeedLineItemsByRfqId()` and wired `use-rfq-line-items` mock mode to consume seeded requisition line items instead of an empty list.
- Added `src/data/seed.test.ts` to verify seeded requisitions expose line items, and updated the mock-mode hook test to assert the seed-backed contract.

## 2026-04-18 RFQ Line Item Section Groups

- Seeded requisition line items now include section heading rows and line rows, so mock-mode requisition line items show grouped sections instead of a flat list.
- The line-items page now renders section rows in both table and grid modes while keeping live API payloads unchanged.
- Added page coverage for grouped mock rows so the section headings and nested line items stay visible in mock-mode UI.

## 2026-04-21 Projects Form Field Simplification

- Project create/edit forms no longer ask for `client_id`; projects are treated as tenant-internal grouping records for buyer-owned requisitions.
- Project manager input on both create and edit views is now a select dropdown sourced from tenant users (with fallback to current authenticated user when needed).
- Projects list/detail UI removed client-facing display rows tied to `client_id` to align with the internal-project model.
- `use-create-project` payload contract no longer requires `client_id`, and response normalization accepts optional `client_id` / `project_manager_id`.
- Verification:
  - `cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-create-project.test.ts` -> PASS.
  - `cd apps/atomy-q/WEB && npx vitest run 'src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx' src/hooks/use-create-rfq-line-item.test.ts src/hooks/use-create-project.test.ts` -> PASS (10 tests).
  - `cd apps/atomy-q/WEB && npx playwright test tests/auth.spec.ts tests/dashboard-nav.spec.ts tests/settings-users-smoke.spec.ts tests/rfq-workflow.spec.ts tests/rfq-line-items.spec.ts tests/smoke.spec.ts tests/screen-smoke.spec.ts` -> PASS (13 passed, 1 skipped).
  - `cd apps/atomy-q/WEB && npm run lint` -> PASS with existing warnings outside `projects/page.tsx`, `projects/[projectId]/page.tsx`, and `src/hooks/use-create-project.test.ts`.
  - `cd apps/atomy-q/WEB && npm run build` -> PASS.

## 2026-04-21 Alpha E2E Coverage Split

- Added `tests/alpha-playwright-bootstrap.ts` as the shared authenticated alpha shell bootstrap for feature flags and RFQ count stubs.
- Moved alpha-visible RFQ browser ownership into `tests/rfq-alpha-journeys.spec.ts`, covering list, create, overview, details, line items with save/refresh assertion, vendors, quote intake, comparison runs, approvals, award, and decision trail.
- Deleted the duplicated mocked RFQ owners `tests/rfq-workflow.spec.ts` and `tests/rfq-line-items.spec.ts`; narrowed `tests/rfq-lifecycle-e2e.spec.ts` to the live API create-then-overview smoke path only.
- Added `tests/projects-tasks-smoke.spec.ts` for adjacent non-alpha project list/detail and task inbox/drawer coverage, and kept `tests/settings-users-smoke.spec.ts` as the adjacent non-alpha settings/users owner.
- Trimmed `tests/smoke.spec.ts` to dashboard plus RFQ-list sanity checks and removed project/task ownership from generic smoke coverage.

## 2026-04-21 E2E Stabilization Follow-Up

- `tests/auth.spec.ts`: replaced the flaky login-form mock path with `authenticated dashboard smoke with mocked session` using `stubAlphaSession(page)` so the test behavior matches its assertions.
- `tests/rfq-alpha-journeys.spec.ts`: hardened RFQ routing to `/\/api\/v1\/rfqs(?:\/.*)?(?:\?.*)?$/` and added per-test `routeAlphaRfqApi(page)` setup for list/create/overview/line-items coverage to avoid missing stubs and parallel-test leakage.
- Verification:
  - `cd apps/atomy-q/WEB && npx playwright test tests/auth.spec.ts tests/rfq-alpha-journeys.spec.ts` -> PASS (8 passed, 1 skipped).
  - `cd apps/atomy-q/WEB && npm run test:e2e` -> PASS (16 passed, 2 skipped, 0 failed).

## 2026-04-21 Real API E2E Fix (`E2E_USE_REAL_API=true`)

- `tests/rfq-lifecycle-e2e.spec.ts`: changed the title assertion to a unique locator (`getByRole('link', { name: title })`) to avoid strict-mode ambiguity when the RFQ title appears in more than one visible element.
- `tests/rfq-alpha-journeys.spec.ts`: changed route transitions to `waitUntil: 'domcontentloaded'` to avoid intermittent navigation hangs under parallel E2E load in dev mode.
- `tests/screen-smoke.spec.ts`: replaced a flaky sidebar click path with direct `/rfqs` navigation for stable route-render verification.
- Verification:
  - `cd apps/atomy-q/WEB && E2E_USE_REAL_API=true npx playwright test tests/rfq-lifecycle-e2e.spec.ts` -> PASS.
  - `cd apps/atomy-q/WEB && E2E_USE_REAL_API=true npm run test:e2e` -> PASS (18 passed, 0 skipped, 0 failed).

## 2026-04-21 Review Follow-Up (E2E Stub/CORS Hygiene)

- `tests/rfq-alpha-journeys.spec.ts`: added explicit `OPTIONS` preflight handling for `**/api/v1/awards**`, `**/api/v1/awards/award-alpha-1/signoff`, and `**/api/v1/awards/award-alpha-1/debrief/**` with `204` and request-origin CORS headers before any JSON parsing.
- `tests/screen-smoke.spec.ts`: replaced inline auth/bootstrap setup with shared `stubAlphaSession(page)`; removed custom origin tracking/CORS header builders; refactored `/api/v1/users` and `/api/v1/roles` route stubs to use `fulfillJsonRoute`.
- `tests/screen-smoke.spec.ts`: added a dedicated `/api/v1/rfqs` list stub (regex scoped so `/rfqs/counts` is not shadowed) for stable `/rfqs` navigation coverage.
- `README.md`: updated PowerShell examples to pass lowercase string env values (`'true'` / `'false'`) so runtime comparisons like `process.env.X === 'true'` behave correctly.
- Verification:
  - `cd apps/atomy-q/WEB && npx playwright test tests/rfq-alpha-journeys.spec.ts tests/screen-smoke.spec.ts` -> PASS (7 passed).
