# Vendors Domain Overview

## Purpose

The Vendors domain is the buyer-side vendor reference surface in Atomy-Q alpha. It exposes vendor lookup, search, performance scoring, compliance summary, and award history.

This is not a vendor-portal or supplier-onboarding product. It is the buyer’s operational view of vendor records that already exist in the tenant.

Operational workflows are documented in [workflows.md](./workflows.md).
The vendor state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- The surface is buyer-side only.
- All reads are tenant-scoped.
- Vendor performance is derived from quote-submission readiness and awards won.
- Compliance status is derived from metadata when present, with a fallback based on the vendor status string.
- The controller does not expose a full vendor lifecycle or portal onboarding flow yet.

## Core Entities

- `Vendor`: the tenant-scoped supplier record
- `Performance snapshot`: the derived score and metric breakdown
- `Compliance snapshot`: the derived compliance status and metadata flags
- `Award history`: the recent award list returned for a vendor

## Inputs

- Tenant context
- Vendor search text or status filters
- Vendor identifier for profile, performance, compliance, or history

## Outputs

- Paginated vendor list
- Vendor detail payload
- Performance score and metrics
- Compliance snapshot
- Recent award history

## Dependencies

### Other Atomy-Q domains

- **RFQ** - vendor performance depends on quote submissions.
- **Awards** - award history is built from signed-off awards.
- **Quote Intake** - quote-submission counts feed the performance score.

### Nexus packages

- `Nexus\Vendor`

### External dependencies

- Laravel
- Tenant-scoped Eloquent models
- Derived metric calculations
