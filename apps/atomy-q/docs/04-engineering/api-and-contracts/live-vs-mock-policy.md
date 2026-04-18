# Live Vs Mock Policy

## Policy

- `NEXT_PUBLIC_USE_MOCKS=true` is for local development and demo-only behavior.
- `NEXT_PUBLIC_USE_MOCKS=false` is mandatory for staging, release verification, and design-partner evidence.
- In live mode, the WEB must fail loudly when the API is unavailable, malformed, or undefined, unless the contract explicitly allows emptiness.

## Contract Flow

- API contract is exported from `apps/atomy-q/API` into `apps/atomy-q/openapi/openapi.json`.
- WEB generated client is built from that contract.
- Manual wrapper behavior must be minimized and explicitly justified when still needed.

## Release Rule

No alpha release evidence is valid if the WEB silently falls back to seed data in live mode.
