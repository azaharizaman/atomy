# Normalization Overview

## Purpose

The Normalization domain turns quote source lines into RFQ-aligned data that can be compared safely. It exposes line mapping, manual overrides, conflict resolution, and readiness recalculation for quote submissions.

This domain sits between quote intake and comparison. It is operationally important, but the code is explicit that the current implementation is deterministic and non-LLM.

Operational workflows are documented in [workflows.md](./workflows.md).
The operational state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- The runtime is deterministic and documented as non-LLM.
- Live data that is malformed or unavailable should fail loudly rather than silently degrade.
- Source-line mapping updates must stay tenant-scoped and RFQ-scoped.
- Readiness is recalculated after mapping or conflict resolution.
- Lock and unlock are active response paths, but they are operational signals rather than a durable persisted lock state.

## Core Entities

- `Normalization source line`: the vendor-side line item extracted from a quote submission
- `Normalization conflict`: the issue record attached to a source line
- `RFQ line item`: the comparison target for mapped source lines
- `Quote submission readiness`: the derived blocking/warnings summary used by comparison

## Inputs

- Tenant-scoped RFQ identifier
- Source-line identifiers
- RFQ line identifiers
- Override payloads
- Conflict resolution payloads
- Quote submission readiness context

## Outputs

- Source-line collections
- Normalized-item collections
- Updated mappings and overrides
- Conflict resolution payloads
- Lock/unlock acknowledgments
- Readiness metadata after each material change

## Dependencies

### Other Atomy-Q domains

- **Quote Intake** - normalization operates on quote submissions and source lines.
- **Comparison** - readiness and mapping correctness directly affect comparison finalization.
- **RFQ** - normalization targets RFQ line items.

### Nexus packages

- `Nexus\QuoteIngestion`
- `Nexus\Sourcing`

### External dependencies

- Laravel
- Readiness evaluation service
- Tenant-scoped Eloquent models
