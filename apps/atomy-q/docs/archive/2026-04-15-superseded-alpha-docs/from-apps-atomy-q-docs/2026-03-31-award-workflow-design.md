# Award Workflow Design

Date: 2026-03-31
Scope: `apps/atomy-q/` award path only
Status: Completed on `feature/award-workflow-alpha`

## Goal

Close the first Alpha gap item by making award creation a workflow result, not a CRUD page.

## Design

- `POST /awards` creates an award only when the workflow has selected a winner.
- The request identifies the tenant-scoped `rfq_id`, `comparison_run_id`, and winner `vendor_id`.
- The API derives award amount and savings from existing RFQ/comparison data instead of asking the user to type them.
- `GET /awards` and `GET /awards/{id}` return persisted awards for view-only rendering.
- The award page stays view-only until a workflow action is available; no dedicated create-award page is introduced.

## Non-goals

- Split awards
- Protest handling
- Full approval-chain orchestration
- Manual entry of financial award fields

## Acceptance criteria

- A workflow action can persist an award for a tenant-scoped RFQ.
- Award data can be read back through the API.
- The award page can show live award state instead of seed-only state.
- Cross-tenant award reads fail with 404.
