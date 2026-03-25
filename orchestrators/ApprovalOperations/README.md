# ApprovalOperations (Nexus)

Layer 2 orchestrator for **operational approvals**: template resolution, composition with `Nexus\Workflow` and `Nexus\PolicyEngine`, CQRS-style persistence ports, and tenant-scoped behavior.

## Scope

- **In scope:** Generic approval instances, policy routing, Workflow-driven tasks/strategies, audit-oriented commands.
- **Out of scope:** RFQ quote approval product flows (sourcing domain); framework code (`Illuminate\*`) in this package.

## Dependencies

| Package | Role |
|---------|------|
| `nexus/workflow` | State machine, approval strategies, escalation/delegation |
| `nexus/policy-engine` | Deterministic policy evaluation |
| `psr/log` | Logging port |

## Design

See `docs/superpowers/specs/2026-03-24-nexus-approval-operations-design.md`.

## Testing

```bash
cd orchestrators/ApprovalOperations
composer install
./vendor/bin/phpunit
```

Install from monorepo root when resolving path packages:

```bash
composer update nexus/approval-operations
```
