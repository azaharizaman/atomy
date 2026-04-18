# RFQ Domain Overview

## Scope

The RFQ domain covers the core buyer workflow:
- RFQ creation, listing, detail, and update
- line-item definition
- scheduling fields
- duplicate, draft, transition, and reminder behavior
- vendor invitation linkage and comparison/award handoff

## Applications

- API:
  `apps/atomy-q/API` owns RFQ routes and persistence behavior.
- WEB:
  `apps/atomy-q/WEB` owns the buyer-side RFQ screens and hook layer.

## Nexus Dependencies

Layer 1:
- `packages/Sourcing`
- `packages/Idempotency`

Layer 2:
- `orchestrators/SourcingOperations`

Layer 3:
- `adapters/Laravel/Sourcing`
- Atomy-Q API RFQ controllers, services, and adapters

## Alpha Notes

- RFQ lifecycle is in scope.
- Line items must use live data in live mode.
- Seed fallback is allowed only when `NEXT_PUBLIC_USE_MOCKS=true`.
