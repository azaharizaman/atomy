# Atomy-Q Documentation

This folder is the active documentation home for Atomy-Q, the SaaS quote-comparison application composed of:

- `apps/atomy-q/API` - Laravel 12 API.
- `apps/atomy-q/WEB` - Next.js 16 web app.
- Nexus Layer 1 packages in `packages/`.
- Nexus Layer 2 orchestrators in `orchestrators/`.
- Nexus Layer 3 Laravel adapters in `adapters/Laravel/` and Atomy-Q API app adapters/services.

## Active Documents

- [Alpha Release Plan - 2026-04-15](./ALPHA_RELEASE_PLAN_2026-04-15.md) is the current source of truth for alpha scope, readiness status, release gates, and execution order.

## Archive Policy

Older alpha audits, dated gap plans, and implementation-spec artifacts have been moved under [archive/2026-04-15-superseded-alpha-docs](./archive/2026-04-15-superseded-alpha-docs/). They are historical references only and must not be used as active release instructions.

Package-local `IMPLEMENTATION_SUMMARY.md` files remain in their packages as implementation ledgers. Architecture-wide standards remain under `docs/project/` unless they are Atomy-Q alpha planning documents.
