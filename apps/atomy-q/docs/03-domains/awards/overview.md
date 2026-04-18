# Awards Overview

## Purpose

The Awards domain captures the final business outcome of the RFQ-to-comparison flow: creating an award from finalized comparison evidence, adjusting split details, recording debriefs, handling protests, and signing off the award.

Operational workflows are documented in [workflows.md](./workflows.md).
The award state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- An award can only be created from a finalized comparison run.
- Debrief records and decision-trail events are persisted as part of the award process.
- Split details are editable after creation.
- Protest handling is active and can move an award back out of the signed-off path.
- Signoff is the explicit terminal approval step for the current alpha flow.

## Core Entities

- `Award`: the signed or pending outcome of the comparison process
- `Debrief`: the per-vendor debrief record linked to an award
- `Protest marker`: the protest identifier attached to an award while a protest is open
- `Comparison run`: the frozen evidence bundle required for award creation
- `Decision trail`: the audit trail of award creation, debriefing, and signoff

## Inputs

- Tenant-scoped RFQ identifier
- Finalized comparison run identifier
- Vendor identifier
- Award amount and currency
- Optional split-details payload
- Optional debrief message
- Optional protest reason

## Outputs

- Created award records
- Updated split details
- Debrief payloads
- Protest and protest-resolution payloads
- Signed-off award payloads
- Decision-trail events for award milestones

## Dependencies

### Other Atomy-Q domains

- **RFQ** - awards are linked to the originating RFQ.
- **Comparison** - awards require a finalized comparison run.
- **Quote Intake / Normalization** - the vendor must have a corresponding quote submission for the award path.
- **Vendors** - award history and debrief references are vendor-scoped.

### Nexus packages

- `Nexus\Sourcing`
- `Nexus\Notifier`

### External dependencies

- Laravel
- Database transactions
- Decision-trail recorder
