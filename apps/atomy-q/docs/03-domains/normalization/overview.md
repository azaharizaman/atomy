# Normalization Overview

## Purpose

The Normalization domain turns quote source lines into RFQ-aligned data that can be compared safely. It exposes provider-backed source-line suggestions, manual overrides, conflict resolution, and readiness recalculation for quote submissions.

This domain sits between quote intake and comparison. The current alpha posture is provider-first with manual buyer continuity: provider suggestions can seed the normalize workspace, but buyers can still add, edit, remap, or override values manually when provider output is unavailable, incomplete, or incorrect.

Operational workflows are documented in [workflows.md](./workflows.md).
The operational state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- `AI_MODE=provider` is the supported alpha posture for normalization suggestions.
- Manual continuity remains first-class when provider normalization is degraded or unavailable.
- Live data that is malformed or unavailable should fail loudly rather than silently degrade.
- Source-line mapping updates must stay tenant-scoped and RFQ-scoped.
- Buyer overrides require a structured reason code, and `other` requires a note.
- Readiness is recalculated after mapping, override, manual source-line mutation, or conflict resolution.
- Lock and unlock are active response paths, but they are operational signals rather than a durable persisted lock state.

## Core Entities

- `Normalization source line`: the vendor-side line item extracted from a quote submission
- `Normalization conflict`: the issue record attached to a source line
- `Provider suggestion`: the provider-origin mapping/value snapshot visible in the normalize workspace
- `Buyer override audit`: the append-only reason-coded trail for effective-value changes
- `RFQ line item`: the comparison target for mapped source lines
- `Quote submission readiness`: the derived blocking/warnings summary used by comparison

## Inputs

- Tenant-scoped RFQ identifier
- Source-line identifiers
- RFQ line identifiers
- Override payloads
- Conflict resolution payloads
- Provider provenance and confidence context
- Quote submission readiness context

## Outputs

- Source-line collections
- Normalized-item collections
- Updated mappings and audited overrides
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
