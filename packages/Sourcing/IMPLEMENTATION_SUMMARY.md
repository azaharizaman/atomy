# Nexus Sourcing (`nexus/sourcing`)

## Scope

Layer 1 domain contracts for quotations, sourcing events, normalization payloads, and RFQ lifecycle primitives used in strategic sourcing.

## Contracts

- `QuotationInterface` - tenant, identity, vendor, status, normalized line items (`NormalizationLine`).
- `QuotationQueryInterface` - tenant-scoped reads by sourcing event (`sourcing_event_id`) and by quotation id.
- `QuotationPersistInterface` - write-side contract (CQRS; methods added when persistence is implemented).
- `AwardInterface` - award identity and links to quotation and vendor.
- `SourcingEventInterface` - marker for future sourcing-event aggregate (RFQ/RFP).
- `RfqStatusTransitionPolicyInterface` - shared Alpha transition policy contract for duplicate/save-draft/bulk-action workflows.

## Value objects

- `NormalizationLine` - line id, description, quantity, UOM, unit price, optional `rfq_line_id`.
- `Conflict` - normalization conflict type and message.
- `RfqLifecycleAction` - deterministic lifecycle action normalization for `duplicate`, `save_draft`, `bulk_action`, `transition_status`, and `remind_invitation`.
- `RfqBulkAction` - allowlisted bulk action wrapper for `close` and `cancel`.
- `RfqDuplicationOptions` - copy policy defaults for RFQ duplication; line items are copied, while invitations, quotes, comparison runs, approvals, and activity stay opt-in.
- `RfqLifecycleResult` - framework-agnostic lifecycle outcome carrying action, status, relevant identifiers, and copy/affected counts.

## Exceptions

- `InvalidRfqStatusTransitionException` - thrown when a lifecycle transition is not allowed.
- `RfqLifecyclePreconditionException` - thrown when a lifecycle precondition is not met.
- `UnsupportedRfqBulkActionException` - thrown when a bulk action is not allowlisted.

## Services

- `RfqStatusTransitionPolicy` - default Alpha RFQ transition policy (`draft -> published|cancelled`, `published -> closed|cancelled`, `closed -> awarded|cancelled`).

## Dependencies

- `nexus/vendor` (Composer) for alignment with the vendor domain; this package now includes a local path repository entry so `composer install` works from `packages/Sourcing` in the worktree.

## Laravel adapter

See `adapters/Laravel/Sourcing` (`nexus/laravel-sourcing-adapter`): Eloquent models, migrations `nexus_quotations` and `nexus_sourcing_awards`, `EloquentQuotationRepository` binding `QuotationQueryInterface`.
