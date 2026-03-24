# Nexus Sourcing (`nexus/sourcing`)

## Scope

Layer 1 domain contracts for quotations, sourcing events, and normalization payloads used in strategic sourcing (RFQ-linked quotations).

## Contracts

- `QuotationInterface` — tenant, identity, vendor, status, normalized line items (`NormalizationLine`).
- `QuotationQueryInterface` — tenant-scoped reads by sourcing event (`sourcing_event_id`) and by quotation id.
- `QuotationPersistInterface` — write-side contract (CQRS; methods added when persistence is implemented).
- `AwardInterface` — award identity and links to quotation and vendor.
- `SourcingEventInterface` — marker for future sourcing-event aggregate (RFQ/RFP).

## Value objects

- `NormalizationLine` — line id, description, quantity, UOM, unit price, optional `rfq_line_id`.
- `Conflict` — normalization conflict type and message.

## Dependencies

- `nexus/vendor` (Composer) for alignment with the vendor domain; no runtime coupling in Layer 1 yet beyond Composer metadata.

## Laravel adapter

See `adapters/Laravel/Sourcing` (`nexus/laravel-sourcing-adapter`): Eloquent models, migrations `nexus_quotations` and `nexus_sourcing_awards`, `EloquentQuotationRepository` binding `QuotationQueryInterface`.
