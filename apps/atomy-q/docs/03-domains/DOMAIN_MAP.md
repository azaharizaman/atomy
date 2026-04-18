# Domain Map

This is the route inventory for the implemented Atomy-Q frontend. Behavior rules live in [CROSS_DOMAIN_RULES.md](./CROSS_DOMAIN_RULES.md); navigation details live in [navigation structure analysis](../../WEB/docs/NAV_STRUCTURE_ANALYSIS.md).

## Legend

- `active` = route is part of the normal app surface
- `feature-flagged` = route is present, but hidden when the backend feature is off
- `alpha-deferred` = route is present, but the screen is deferred in alpha mode
- `scaffolded` = route is present for parity, but the section is still a stub

## Routes

| Domain | Route(s) | Status | Owner / notes |
|---|---|---|---|
| Access and onboarding | `/login`, `/register-company`, `/forgot-password`, `/reset-password` | active | Public auth and onboarding flows. |
| Dashboard shell | `/` | active | Main landing page and workspace entry point. |
| Requisition | `/rfqs`, `/rfqs/new` | active | RFQ list, filtering, and creation. |
| RFQ workspace | `/rfqs/[rfqId]/overview`, `/details`, `/line-items`, `/vendors`, `/quote-intake`, `/comparison-runs`, `/award`, `/approvals`, `/decision-trail`, `/quote-intake/[quoteId]/normalize`, `/comparison-runs/[runId]` | active | Workspace layout with the Active Record Menu and RFQ record context. |
| Deferred RFQ sections | `/rfqs/[rfqId]/documents`, `/negotiations`, `/risk` | alpha-deferred | Present in routing, hidden by alpha mode. |
| RFQ section stub | `/rfqs/[rfqId]/[section]` | scaffolded | Navigation parity only. |
| Projects | `/projects`, `/projects/[projectId]` | feature-flagged | Controlled by `useFeatureFlags().projects`. |
| Task Inbox | `/tasks` | feature-flagged | Controlled by `useFeatureFlags().tasks`. |
| Approval Queue | `/approvals`, `/approvals/[id]` | active | Global approval list and detail views. |
| Documents | `/documents` | alpha-deferred | Top-level document surface. |
| Reporting | `/reporting` | alpha-deferred | Top-level reporting surface. |
| Settings | `/settings`, `/settings/users`, `/settings/scoring-policies`, `/settings/templates`, `/settings/integrations`, `/settings/feature-flags` | alpha-deferred | Workspace administration surface. |

## Shared systems

- `src/providers/auth-provider.tsx` and `src/store/use-auth-store.ts` manage session state.
- `src/config/nav.ts` owns the shared nav labels.
- `src/lib/alpha-mode.ts` controls alpha visibility.
- `src/hooks/use-feature-flags.ts` gates Projects and Task Inbox.
- `src/data/seed.ts` supports mock-mode seed data.

For the live implementation status of each surface, see [implementation summary](../../WEB/IMPLEMENTATION_SUMMARY.md).
