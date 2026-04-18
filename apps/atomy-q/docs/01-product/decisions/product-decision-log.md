# Product Decision Log

## Active Decisions

- Alpha is a narrow buyer-side quote-comparison journey, not the full procurement platform.
- `tenant_id` is the canonical isolation key for Atomy-Q alpha.
- Mock mode is local-only and is never valid evidence for staging or release readiness.
- Deterministic quote intelligence is the supported alpha runtime mode until LLM provider wiring is production-ready.
- Users & Roles remains in alpha only as a minimal tenant-scoped admin surface.
- Non-alpha modules are hidden or explicitly deferred rather than exposed with placeholder success.
