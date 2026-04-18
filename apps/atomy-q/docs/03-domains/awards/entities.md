# Awards Entities

This document defines the core business entities used by the Awards domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For award state semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - Award

The award is the tenant-scoped business decision that selects a vendor for an RFQ.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique award identifier. | Required; tenant-scoped. |
| `rfq_id` | Source RFQ. | Required. |
| `comparison_run_id` | Finalized evidence bundle. | Required for creation. |
| `vendor_id` | Selected vendor. | Required. |
| `status` | Award lifecycle state. | `pending`, `protested`, `signed_off`. |
| `amount` | Awarded value. | Required; decimal; non-negative. |
| `currency` | Award currency. | Required; three-letter code. |
| `split_details` | Optional award split payload. | Optional array. |
| `protest_id` | Open protest marker. | Present only while a protest is open. |
| `signoff_at` | Signoff timestamp. | Null until signed off. |
| `signed_off_by` | Signing user. | Null until signed off. |

### Relationships

- An award belongs to one RFQ.
- An award belongs to one comparison run.
- An award can have many handoff records in the model layer, even though the controller does not expose them directly.

### Business Rules

- Award creation must use finalized comparison evidence.
- Split details can be edited without changing the state.

## Entity 002 - Debrief

The debrief is the vendor-facing explanation record linked to an award.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `award_id` | Parent award. | Required. |
| `rfq_id` | Source RFQ. | Required. |
| `vendor_id` | Debrief recipient. | Required. |
| `message` | Optional explanation. | Optional text. |
| `debriefed_at` | Timestamp of the debrief. | Set on creation. |

### Relationships

- A debrief belongs to an award and to the originating RFQ.
- The controller creates at most one debrief row for the same award/vendor pair through `firstOrCreate`.

### Business Rules

- Debriefing is audit-oriented and should not overwrite the award itself.

## Entity 003 - Protest Marker

The protest marker is the temporary identifier that shows a protest is open on an award.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `protest_id` | Open protest identifier. | Generated when the award is protested. |
| `reason` | Optional protest reason. | Returned in the protest response only. |

### Relationships

- The protest marker lives on the award record.

### Business Rules

- Resolving a protest requires the exact protest identifier.

## Related Docs

- Workflow details: [workflows.md](./workflows.md)
- Award lifecycle: [lifecycle.md](./lifecycle.md)
