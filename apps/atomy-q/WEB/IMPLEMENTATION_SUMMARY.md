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
| **E2E Testing** | ✅ | Playwright: RFQ list → workspace overview (`tests/rfq-workflow.spec.ts`); auth forgot/reset with mocked API (`tests/auth.spec.ts`). Full suite not signed off on Fedora — run on Ubuntu or CI before partner invite. |
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
