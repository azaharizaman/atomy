# Normalization Entities

This document defines the core business entities used by the Normalization domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For state semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - Normalization Source Line

The source line is the vendor-side line item extracted from a quote submission.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique source-line identifier. | Required; tenant-scoped. |
| `quote_submission_id` | Parent quote submission. | Required. |
| `rfq_line_item_id` | Target RFQ line. | Optional until mapped. |
| `source_description` | Vendor-provided description. | Required. |
| `source_quantity` | Vendor-provided quantity. | Optional decimal text. |
| `source_uom` | Vendor-provided unit of measure. | Optional. |
| `source_unit_price` | Vendor-provided unit price. | Optional decimal text. |
| `raw_data` | Parsed source payload. | Array; may contain override data. |
| `sort_order` | Display order. | Integer. |
| `ai_confidence` | Optional scoring hint. | Optional decimal text. |
| `taxonomy_code` | Optional classification code. | Optional. |
| `mapping_version` | Mapping version tag. | Optional. |

### Relationships

- A source line belongs to a quote submission.
- A source line may map to exactly one RFQ line item.
- A source line can have many conflicts.

### Business Rules

- Raw data overrides are stored inside the source line payload.
- Readiness is recalculated after mapping or override changes.

## Entity 002 - Normalization Conflict

The conflict records a normalization problem that still needs resolution or explanation.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique conflict identifier. | Required; tenant-scoped. |
| `normalization_source_line_id` | Parent source line. | Required. |
| `conflict_type` | Conflict category. | Free-form string in the controller. |
| `resolution` | Human or system resolution. | Optional until resolved. |
| `resolved_at` | Resolution timestamp. | Null until resolved. |
| `resolved_by` | Resolving user. | Null until resolved. |

### Relationships

- A conflict belongs to one source line.
- A conflict can be resolved independently of mapping changes.

### Business Rules

- Resolved conflicts still matter to readiness until the submission is re-evaluated.

## Entity 003 - RFQ Line Item

The RFQ line item is the normalization target for source-line mapping.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique RFQ line identifier. | Required. |
| `rfq_id` | Parent RFQ. | Required. |
| `description` | RFQ line description. | Required. |
| `quantity` | Target quantity. | Optional decimal. |
| `uom` | Unit of measure. | Optional. |
| `unit_price` | Reference unit price. | Optional decimal. |
| `currency` | Currency code. | Optional. |

### Relationships

- A source line can only map to an RFQ line from the same RFQ.

### Business Rules

- Cross-RFQ mapping is rejected.

## Entity 004 - Readiness Snapshot

The readiness snapshot is the derived per-submission and per-RFQ readiness output.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `has_blocking_issues` | Whether comparison is blocked. | Derived boolean. |
| `blocking_issue_count` | Count of unresolved blockers. | Derived integer. |
| `next_status` | Suggested submission state after evaluation. | Derived string. |

### Relationships

- The snapshot is produced by the quote-submission readiness service.

### Business Rules

- Normalization writes should always re-evaluate readiness after a material change.

## Related Docs

- Workflow details: [workflows.md](./workflows.md)
- Operational state notes: [lifecycle.md](./lifecycle.md)
