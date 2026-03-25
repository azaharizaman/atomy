# ApprovalOperations — Implementation Summary

## Shipped (2026-03-25)

- **PR Review Fixes:**
    - Migration: Updated `2026_03_25_000001` down migration to assign unique ULIDs per row during workflow nullability reversion.
    - Orchestrator: Implemented `ApprovalStatus` enum and `InvalidStartApprovalCommandException` domain exception; updated `ApprovalProcessCoordinator` and `ApprovalInstanceReadModel` to use them.
    - API: Reordered provider imports in `bootstrap/providers.php` and strengthened `OperationalApprovalApiTest` assertions (ID non-empty string validation).
    - Infrastructure: Fixed root `composer.json` repository mapping for nested adapters to enable monorepo testing.

## Shipped (2026-03-24)

- **Layer 2 (`nexus/approval-operations`):** CQRS-style contracts (templates, instances, comments), DTOs, domain exceptions, `ApprovalTemplateResolver`, `ApprovalProcessCoordinator` (PolicyEngine gate + workflow bridge port + instance/comment persistence), `ApprovalSlaViewBuilder` (SLA read model derived from instance metadata), and `ApprovalInstancePageReadModel` for paged inbox/list views.
- **Layer 3 (`nexus/laravel-approval-operations-adapter`):** Migrations (`operational_approval_*` tables), Eloquent models, persistence implementations, `GeneratingOperationalWorkflowBridge` (persists `operational_approval_workflows` rows and updates state on decisions).
- **Atomy-Q API:** `OperationalApprovalController`, routes under `/api/v1/operational-approvals/*`, `LaravelUlidGenerator`, exception mapping (404 / 403) in `bootstrap/app.php`.
- **Docs:** `docs/project/NEXUS_PACKAGES_REFERENCE.md`, `docs/project/ARCHITECTURE.md`, `apps/atomy-q/API_ENDPOINTS.md` section 30.
- **Tests:** Orchestrator unit tests; API feature tests `OperationalApprovalApiTest`.

## Shipped (2026-03-26)

- **Hardening:** `OperationalApprovalController` now wraps start/decision flows in DB transactions, `GeneratingOperationalWorkflowBridge::applyDecision()` fails fast when the workflow row is missing, and the new operational workflow table enforces a foreign key to `operational_approval_instances`.
- **Policy:** `PolicyEngineInterface` is bound to an app-level engine that evaluates stored policy definitions (table `policy_definitions`) using `Nexus\PolicyEngine`, logs fail-closed denials, and denies on missing/invalid policies.

## Follow-ups

- Decide whether the local operational workflow table remains the long-term read model or whether `OperationalWorkflowBridgeInterface` should be re-pointed to `Nexus\Workflow` repositories.
- Run `php artisan scramble:export --path=../openapi/openapi.json` (requires DB) then `npm run generate:api` in `apps/atomy-q/WEB` to refresh the TS client.
- POST mutating routes use `idempotency` middleware + named routes (`v1.operational-approvals.instances.store`, `v1.operational-approvals.instances.decisions.store`).

## Last updated

2026-03-26
