# ApprovalOperations — Implementation Summary

## Shipped (2026-03-24)

- **Layer 2 (`nexus/approval-operations`):** CQRS-style contracts (templates, instances, comments), DTOs, domain exceptions, `ApprovalTemplateResolver`, `ApprovalProcessCoordinator` (PolicyEngine gate + workflow bridge port + persistence), `ApprovalSlaViewBuilder` (stub SLA read model).
- **Layer 3 (`nexus/laravel-approval-operations-adapter`):** Migrations (`operational_approval_*` tables), Eloquent models, persistence implementations, `GeneratingOperationalWorkflowBridge` (ULID correlation until Workflow wiring).
- **Atomy-Q API:** `OperationalApprovalController`, routes under `/api/v1/operational-approvals/*`, `AtomyPermissivePolicyEngine` (alpha stub), `LaravelUlidGenerator`, exception mapping (404 / 403) in `bootstrap/app.php`.
- **Docs:** `docs/project/NEXUS_PACKAGES_REFERENCE.md`, `docs/project/ARCHITECTURE.md`, `apps/atomy-q/API_ENDPOINTS.md` section 30.
- **Tests:** Orchestrator unit tests; API feature tests `OperationalApprovalApiTest`.

## Follow-ups

- Replace permissive policy stub with real `PolicyEvaluator` + stored policies.
- Wire `OperationalWorkflowBridgeInterface` to `Nexus\Workflow` repositories when instantiate/apply are fully implemented.
- Run `php artisan scramble:export --path=../openapi/openapi.json` (requires DB) then `npm run generate:api` in `apps/atomy-q/WEB` to refresh the TS client.
- Optional: idempotency middleware on POST routes with stable `OperationRef`.

## Last updated

2026-03-24
