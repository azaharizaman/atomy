# Current State

## Status

Atomy-Q is in design-partner alpha release preparation for a narrow quote-comparison journey delivered through:
- `apps/atomy-q/API`: Laravel 12 API.
- `apps/atomy-q/WEB`: Next.js 16 WEB application.

The current release has completed the execution work for Section 9 Tasks 1 to 8 from the historical alpha plan. The only active release task is:

- `Section 9 Task 9: Final Alpha Release Gate`

Canonical active release docs:
- [`02-release-management/current-release/release-overview.md`](./02-release-management/current-release/release-overview.md)
- [`02-release-management/current-release/release-plan.md`](./02-release-management/current-release/release-plan.md)
- [`02-release-management/current-release/release-checklist.md`](./02-release-management/current-release/release-checklist.md)

## Current Product Posture

Supported alpha journey:
- tenant registration or login
- RFQ creation and line-item setup
- vendor invitation
- quote submission and normalization
- comparison finalization
- award creation and signoff
- decision-trail verification

Current alpha constraints:
- `NEXT_PUBLIC_USE_MOCKS=false` is mandatory for staging and release validation.
- `QUOTE_INTELLIGENCE_MODE=deterministic` is the supported alpha runtime.
- `QUEUE_CONNECTION=sync` is the supported staging posture for the main alpha smoke.
- non-alpha surfaces remain hidden or explicitly deferred.

## Nexus Dependency Posture

Atomy-Q depends on Nexus first-party layers:
- Layer 1 packages for domain contracts and value objects.
- Layer 2 orchestrators for workflow boundaries.
- Layer 3 Laravel adapters for persistence and framework integration.

Canonical dependency map:
- [`04-engineering/architecture/nexus-dependencies.md`](./04-engineering/architecture/nexus-dependencies.md)
