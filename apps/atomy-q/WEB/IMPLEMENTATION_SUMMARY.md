# Implementation Summary

## Implementation Status

| Feature | Status | Notes |
| :--- | :--- | :--- |
| **Project Setup** | ✅ | Next.js 16, Tailwind, TypeScript initialized. |
| **Authentication** | ✅ | JWT Auth with refresh token flow, Zustand store, redesigned login, plus forgot/reset password flows. |
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
| **Quote Intake** | ✅ | Quote list, detail, normalize, comparison freeze, award, and approvals screens now consume live API data when `NEXT_PUBLIC_USE_MOCKS=false`. Live quote rows preserve nullable fields, normalize confidence/blocks strictly, reject empty-string unit prices, scope normalization source lines to the active `quoteId`, and expose accessible controlled selection controls on normalization rows. Award debriefs now use user-entered draft text instead of a hard-coded template message. Mock/demo branches remain available for local seed mode. |
| **Approvals** | 🚧 | Global queue `/approvals` + detail `/approvals/[id]` call API; RFQ-scoped approval URLs now use the live pending-approval list, forward the RFQ filter to the hook, and normalize backend priority values before rendering. |
| **Turbopack Root Config** | ✅ | Resolve `tailwindcss` from WEB config dir to avoid parent-root module resolution. |

## Next Steps
1.  Replace mocked RFQ list/detail data with real payload mapping (see `BACKEND_API_GAPS.md` for required fields/params).
2.  Continue refining quote intake, comparison, award, and approval screens as the remaining backend surfaces are productized.
3.  Implement Vendor Management and Invitation lifecycle on top of the now-live quote flow.
4.  Expand Playwright coverage per workflow slice.

## 2026-03-19 PR Remediation
- Project ACL editor now uses stable draft-local row IDs to prevent React remounting when `userId` is edited.
- Project ACL save flow now blocks empty ACL submissions both in button-disabled state and at click handler guard.
- `use-update-project-acl` now validates mapped roles at runtime against canonical allowed roles and fails fast on invalid payloads.
- Seed data now includes realistic enterprise RFQ presets (software renewal, SOC services, WAN upgrade, facilities maintenance, HRIS partner) in addition to generated lifecycle data.
