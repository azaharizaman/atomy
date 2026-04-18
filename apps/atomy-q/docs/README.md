# Atomy-Q Execution Documentation

This folder is the single source of truth for Atomy-Q project execution and management documentation.

Scope of this docs set:
- Product scope, release scope, and supported journeys for the Atomy-Q SaaS application.
- Engineering decisions and operating model for the two Atomy-Q applications:
  `apps/atomy-q/API` (Laravel API) and `apps/atomy-q/WEB` (Next.js frontend).
- Delivery governance, QA evidence, release management, staging readiness, and Nexus dependency mapping.

This docs set is not for user-facing product documentation. User-facing application documentation stays with the owning app under `apps/atomy-q/API` or `apps/atomy-q/WEB`.

## Start Here

- [`INDEX.md`](./INDEX.md): canonical navigation map.
- [`CURRENT_STATE.md`](./CURRENT_STATE.md): current release posture, active work, and major constraints.
- [`01-product/scope/alpha-scope.md`](./01-product/scope/alpha-scope.md): current alpha scope.
- [`02-release-management/current-release/release-plan.md`](./02-release-management/current-release/release-plan.md): active release execution plan.
- [`02-release-management/current-release/release-checklist.md`](./02-release-management/current-release/release-checklist.md): current release evidence ledger.
- [`04-engineering/standards/`](./04-engineering/standards/): coding, branching, definition of done, testing, and security standards.

## Structure

- `01-product/`: scope, supported flows, and product decisions.
- `02-release-management/`: current release execution, blockers, checklist, runbook, and change control.
- `03-domains/`: durable domain references for auth, RFQ, quote intake, normalization, comparison, awards, users, and related alpha areas.
- `04-engineering/`: system architecture, Nexus dependencies, standards, and API/client governance.
- `05-qa/`: QA plans and checklists.
- `06-operations/`: environment and runbook material outside release-specific execution.
- `07-history/`: dated historical plans, specs, and superseded release artifacts.
- `templates/`: reusable templates for new docs.

## Documentation Rules

- Each durable topic must have one canonical current file.
- Release/task docs must not become the only place where a durable decision lives.
- Dated documents belong in `07-history/` unless they are the current release artifact.
- If a release or task changes a durable rule, update the relevant domain or engineering doc in the same change.
