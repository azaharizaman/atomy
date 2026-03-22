# Idempotency — Valuation Matrix

| Metric | Estimate |
|--------|----------|
| Public API surface | Low–medium (service + contracts + VOs) |
| Cyclomatic complexity (service) | Medium (state branches) |
| Test coverage (lines, v1) | ~66%+ (package-local run) |
| Layer 1 purity | High (no framework/DB) |
| Operational risk | Low until L3 persistence added |
| Duplication check source | See `docs/project/NEXUS_PACKAGES_REFERENCE.md` (idempotency row) and this package’s `IMPLEMENTATION_SUMMARY.md`; keep both aligned when the implementation changes. |
