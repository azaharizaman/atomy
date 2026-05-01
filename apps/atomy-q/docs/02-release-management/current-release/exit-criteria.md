# Release Exit Criteria

Atomy-Q exits the final alpha release gate only when:
- Task 9 completion criteria in [`release-plan.md`](./release-plan.md) are satisfied.
- Staging evidence is captured in [`release-checklist.md`](./release-checklist.md).
- Engineering, product, and operator/staging ownership sign-off is recorded.

If the staging smoke is missing or incomplete, the release remains internal alpha only.

## Current Status - 2026-05-01

The release remains internal alpha only because the current evidence set is missing:
- PostgreSQL alpha API matrix closure on the configured local/staging posture,
- deployed WEB and API staging URLs,
- mocks-off staging smoke artifacts,
- customer/operator disclosure text for the selected posture,
- engineering, product, and operator/staging sign-offs.
