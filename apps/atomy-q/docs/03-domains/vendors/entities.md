# Vendors Entities

This document defines the core business entities used by the Vendors domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For state semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - Vendor

The vendor is the tenant-scoped supplier record that the buyer side works with.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique vendor identifier. | Required; tenant-scoped. |
| `name` | Vendor legal or display name. | Required. |
| `trading_name` | Alternate public-facing name. | Optional. |
| `registration_number` | Company registration reference. | Optional. |
| `tax_id` | Tax registration reference. | Optional. |
| `country_code` | Country code. | Required. |
| `email` | Vendor contact email. | Required. |
| `phone` | Vendor contact phone. | Optional. |
| `status` | Vendor status string. | Treated as an opaque string by the controller. |
| `onboarded_at` | When the vendor became active in the workspace. | Optional. |
| `metadata` | Arbitrary vendor metadata. | Array. |

### Relationships

- A vendor belongs to one tenant.
- A vendor can appear in quote submissions and awards for that tenant.

### Business Rules

- Vendor reads must remain tenant-scoped.
- The controller does not enforce a formal vendor status enum yet.

## Entity 002 - Performance Snapshot

The performance snapshot summarizes how the vendor has performed in the tenant.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `score` | Derived vendor score. | Numeric from `0` to `100`. |
| `quotes_submitted` | Total quotes submitted. | Integer. |
| `quotes_ready` | Quotes that reached ready state. | Integer. |
| `awards_won` | Awards won by the vendor. | Integer. |
| `average_confidence` | Average submission confidence. | Optional decimal. |

### Relationships

- The snapshot is derived from quote submissions and awards.

### Business Rules

- No quotes submitted means a zero score.

## Entity 003 - Compliance Snapshot

The compliance snapshot is the derived compliance state for the vendor.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `status` | Compliance state. | Derived from metadata or fallback logic. |
| `kyc_verified` | KYC flag. | Boolean. |
| `sanctions_screened` | Screening flag. | Boolean. |
| `last_checked_at` | Compliance review timestamp. | Optional string timestamp from metadata. |

### Relationships

- Compliance data is read from the vendor metadata blob when present.

### Business Rules

- If no explicit compliance status is present, active vendors default to `compliant` and other vendors default to `review_required`.

## Entity 004 - Award History Entry

The award history entry is the recent award record returned on the vendor history tab.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `award_id` | Award identifier. | Required. |
| `rfq_id` | Source RFQ. | Required. |
| `status` | Award state. | Returned as a raw status string. |
| `amount` | Awarded amount. | Optional decimal. |
| `currency` | Award currency. | Required. |
| `signed_off_at` | Signoff timestamp. | Optional. |
| `created_at` | Award creation timestamp. | Optional. |

### Relationships

- History entries are read from signed-off or otherwise existing awards for the vendor.

### Business Rules

- The history tab is a read model; it does not mutate awards.

## Related Docs

- Workflow details: [workflows.md](./workflows.md)
- Vendor lifecycle notes: [lifecycle.md](./lifecycle.md)
