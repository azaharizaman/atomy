# Staging Environment

Canonical release-facing staging guidance lives in:
- [`../../02-release-management/current-release/staging-runbook.md`](../../02-release-management/current-release/staging-runbook.md)

Current supported staging posture:
- `NEXT_PUBLIC_USE_MOCKS=false`
- `QUOTE_INTELLIGENCE_MODE=deterministic`
- `QUEUE_CONNECTION=sync`
- real writable storage disk for quote uploads

Use the release-specific runbook for bring-up, smoke execution, and evidence capture.
