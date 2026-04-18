# Approvals Lifecycle

This file describes the approval status model itself. The queue and decision workflows are documented in [workflows.md](./workflows.md).

## Purpose

The Approvals lifecycle defines how an approval record moves from a pending request into a terminal decision, while preserving enough tenant-scoped audit data for the RFQ workspace and approval queue to explain what happened.

The current implementation spans the Atomy-Q API approval models and controller, the RFQ workspace, and the generic ApprovalOperations package that sits below the app-specific surface.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `pending` | Default state for a new approval request. | `apps/atomy-q/API/app/Models/Approval.php`, `apps/atomy-q/API/database/migrations/2026_03_11_000013_create_approvals_table.php`, `apps/atomy-q/WEB/src/hooks/use-approvals.ts` |
| `approved` | Final approval decision recorded by the controller. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`, `apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php` |
| `rejected` | Final rejection decision recorded by the controller. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`, `apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php` |
| `returned` | Approval sent back to the requester for revision. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php` as a stubbed action, not a persisted state transition yet. |
| `reassigned` | Approval ownership moved to another user. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php` as a stubbed action, not a persisted state transition yet. |
| `snoozed` | Review deferred until a later time. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php` exposes `snooze` as a stubbed action; the model stores `snoozed_until`. |
| `evidence_requested` | Additional supporting evidence has been requested. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php` as a stubbed action, not a persisted state transition yet. |

## Entry Criteria

### pending

- An upstream workflow creates a tenant-scoped approval row.
- The approval is linked to an RFQ and may optionally point to a comparison run.
- The approval starts in the queue before a human decision is made.

### approved

- The approval is in `pending` state.
- The approve path finds the approval in the current tenant.
- If a comparison run is linked, the run must be finalized.
- The frozen comparison snapshot must be present.
- All related quote submissions must be ready and free of blocking normalization issues.

### rejected

- The approval is in `pending` state.
- The reject path finds the approval in the current tenant.
- A rejection reason may be supplied but is not strictly required by the controller.

### returned / reassigned / snoozed / evidence_requested

- These states are represented as workflow intents in the controller surface.
- The current controller returns stubbed or empty responses for these actions.
- The business meaning is clear, but the persistence flow is not yet complete.

## Transitions

### pending -> approved

- Trigger: a buyer approves the item from the detail screen.
- Current Atomy path: `ApprovalController::approve()`.
- Side effects: `approved_at` and `approved_by` are set, and optional notes may be updated from the provided reason.

### pending -> rejected

- Trigger: a buyer rejects the item from the detail screen.
- Current Atomy path: `ApprovalController::reject()`.
- Side effects: `approved_at` and `approved_by` are set, and the rejection reason may be stored in `notes`.

### pending -> returned

- Trigger: the approval is routed back for more work.
- Current Atomy path: `ApprovalController::return_()` returns a stubbed response only.

### pending -> reassigned

- Trigger: the approval is assigned to another reviewer.
- Current Atomy path: `ApprovalController::reassign()` returns a stubbed response only.

### pending -> snoozed

- Trigger: the review is deferred until a later time.
- Current Atomy path: `ApprovalController::snooze()` returns a stubbed response only.

### pending -> evidence_requested

- Trigger: the reviewer asks for supporting evidence.
- Current Atomy path: `ApprovalController::requestEvidence()` returns a stubbed response only.

### any -> history entry

- Trigger: an approval action or audit event occurs.
- Current Atomy path: `approval_history` rows are seeded and modeled, but `ApprovalController::history()` still returns an empty list.

## Audit Rules

- Decision actions should be captured in `approval_history` so the queue and detail view can explain the timeline.
- The approval itself is the source of truth for the current state.
- History rows should not be used as a replacement for the approval status field.
- Tenant scope must apply to both the approval row and its audit trail.

## Dependencies

### Other Atomy-Q domains

- **RFQ** - provides the requisition context and consumes approval counts.
- **Comparison** - provides the frozen snapshot and readiness checks for approve actions.
- **Auth** - supplies tenant and user context for ownership and authorization.

### Nexus packages

- `Nexus\ApprovalOperations`
- `Nexus\PolicyEngine`

### External dependencies

- Laravel
