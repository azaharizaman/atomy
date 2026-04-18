# RFQ Lifecycle

This file describes the RFQ status model itself. The draft/edit/duplicate workflows are documented in [workflows.md](./workflows.md).

## Purpose

The RFQ lifecycle defines how a requisition moves from draft creation through publication, closure, and award or cancellation.

The current implementation spans the Atomy-Q API RFQ controller, the sourcing lifecycle adapter, the RFQ workspace, and the downstream quote-intake/comparison/award surfaces that consume the RFQ state.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `draft` | Initial editable state for a new or duplicated RFQ. | `apps/atomy-q/API/app/Models/Rfq.php`, `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecyclePersist.php`, RFQ draft/edit forms |
| `published` | RFQ is visible to vendors and the quote lifecycle can progress. | `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`, `apps/atomy-q/API/app/Console/Commands/SeedRfqFlowCommand.php` |
| `closed` | RFQ is closed to further submission and can progress toward comparison or award. | `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecyclePersist.php`, `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php` |
| `awarded` | RFQ has a completed award outcome. | `apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php`, `apps/atomy-q/API/tests/Feature/Api/RfqCountsTest.php` |
| `cancelled` | RFQ is terminated without moving to award. | `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecyclePersist.php`, seed and count logic |

## Entry Criteria

### draft

- The RFQ has just been created or duplicated.
- The buyer can still edit core fields and line items.

### published

- The buyer has moved the RFQ out of draft.
- Quote intake and vendor-facing workflow can proceed.

### closed

- The buyer or a bulk operation closes the RFQ.
- Further quote intake should stop, and the record can move into comparison or award handling.

### awarded

- A final comparison and award flow has completed.
- The RFQ is treated as completed for the main buyer workflow.

### cancelled

- The buyer or a bulk operation ends the RFQ without award.

## Transitions

### draft -> published

- Trigger: the buyer publishes the RFQ.
- Current Atomy path: `RfqLifecycleCoordinatorInterface::transitionStatus()` through `RfqController::updateStatus()`.

### draft -> cancelled

- Trigger: the buyer abandons or cancels the RFQ before publication.
- Current Atomy path: supported by the sourcing lifecycle and bulk-close/cancel behavior.

### published -> closed

- Trigger: the buyer closes the RFQ.
- Current Atomy path: `transitionStatus()` and bulk close behavior.

### published -> cancelled

- Trigger: the buyer cancels the RFQ.
- Current Atomy path: `transitionStatus()` and bulk cancel behavior.

### closed -> awarded

- Trigger: the downstream comparison and award workflow completes.
- Current Atomy path: award logic and seeded workflow flows treat this as the completed business outcome.

### closed -> cancelled

- Trigger: the buyer cancels a closed RFQ instead of awarding it.
- Current Atomy path: supported by the lifecycle adapter and bulk action flow.

## Draft and Bulk Rules

- Draft save only changes editable fields and must preserve tenant scope.
- Duplicate creates a brand-new draft RFQ.
- Bulk actions are limited to close and cancel.
- Unsupported bulk actions fail with a domain exception.
- The RFQ number generator retries on uniqueness collisions rather than emitting a synthetic identifier.

## Dependencies

### Other Atomy-Q domains

- **Quote Intake** - quote submission state feeds RFQ readiness and summary counts.
- **Comparison** - final comparison requires ready quotes from the RFQ.
- **Approvals** - approval counts are derived from RFQ-linked approval rows.
- **Awards** - award creation consumes closed/completed RFQs.

### Nexus packages

- `Nexus\Sourcing`
- `Nexus\SourcingOperations`
- `Nexus\Idempotency`

### External dependencies

- Laravel
