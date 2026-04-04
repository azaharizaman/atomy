# SourcingOperations - Implementation Summary

## Shipped (2026-04-03)

- Introduced the RFQ lifecycle orchestration boundary in `SourcingOperationsCoordinator`.
- Defined Layer 2 contracts for RFQ reads, persistence, line-item reads/copying, and invitation reminder coordination.
- Implemented DTOs for duplicate, save-draft, bulk-action, transition, reminder, and lifecycle outcome flows.
- Wired the coordinator to the Layer 1 RFQ transition policy and bulk-action allowlist.
- Implemented tenant-scoped failure semantics for missing RFQs and invitations.
- Covered duplicate, draft save, bulk action, status transition, invitation reminder, and outcome validation with unit tests.

## Hardening (2026-04-04)

- Added orchestrator-local `SourcingRfqStatusTransitionPolicyInterface` and `SourcingTransactionManagerInterface` so `SourcingOperationsCoordinator` no longer depends directly on a Layer 1 policy contract and can wrap duplicate flows in an adapter-owned transaction.
- Hardened duplicate workflow integrity: coordinator now executes RFQ duplication and line-item copy in one transaction boundary; Laravel persistence now generates `rfq_number` and saves the duplicate RFQ atomically with retry on unique-key collisions.
- Fixed draft patch semantics by tracking field presence in `SaveRfqDraftCommand`, allowing explicit nulls for nullable draft fields instead of collapsing them back to the stored value.
- Added bulk-action precondition checking so preloaded records must match the requested RFQ id set exactly before persisted updates run.
- Changed invitation reminders to send first and only mark `reminded_at` after delivery dispatch succeeds; invitation DTOs now carry `channel` end to end.
- Expanded unit coverage for transaction wrapping, nullable draft clearing, preloaded bulk-record mismatches, and reminder success/failure ordering.

## Follow-ups

- Keep the orchestration layer aligned with future Layer 1 lifecycle contract changes.

## Last updated

2026-04-05
