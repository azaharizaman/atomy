# Vendors Lifecycle

This file describes the vendor state model itself. The operational list, profile, performance, compliance, and history flows are documented in [workflows.md](./workflows.md).

## Purpose

The Vendors domain does not currently expose a formal vendor lifecycle state machine in the controller.

The current implementation treats `status` as an opaque string, with compliance and performance derived from the vendor record and related tenant data.

## State Model

There is no canonical vendor lifecycle enum enforced by the controller yet.

### What the code actually uses

- `status` is stored and filtered as a string.
- `onboarded_at` signals that the vendor has been activated in the workspace.
- Compliance may be derived from `metadata.compliance_status`.
- Active vendors receive a `compliant` fallback compliance state when no explicit compliance status exists.

## Transition Notes

- The controller does not enforce create, suspend, reactivate, or archive transitions for vendors.
- Any richer vendor lifecycle should be documented only after the code adds an explicit state machine.

## Operational Rules

- Performance history and compliance snapshots are read models, not lifecycle states.
- All vendor reads must remain tenant-scoped.

## Related Docs

- Vendor workflows: [workflows.md](./workflows.md)
- Vendor entities: [entities.md](./entities.md)
