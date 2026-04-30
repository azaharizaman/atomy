# Current Blockers

## Controlling Contract

Use [`alpha-launch-readiness-superseding-spec-2026-04-30.md`](./alpha-launch-readiness-superseding-spec-2026-04-30.md) for final go/no-go interpretation. Older blocker labels remain useful history, but they do not override the superseding spec.

## Closed Locally

- `A1`: deterministic quote-intelligence mode is documented and wired as the supported alpha default.
- `A2`: compare-to-award path has local API, WEB, and Playwright proof.
- `A3`: live-mode seed fallback has been removed from the golden path.
- `A4`: non-alpha surfaces are hidden or explicitly deferred for alpha.
- `A5`: minimal tenant-scoped users/roles flow is shipped for alpha.
- `A6`: OpenAPI export and generated WEB client alignment evidence exists locally.

## Still Open Until Task 9 Closes

- `A7`: staging operations readiness is not complete until a true deployed mocks-off smoke is recorded.
- `A8`: release evidence fragmentation is reduced by the new docs structure, but the final alpha release decision still depends on Task 9 completion and explicit sign-off.
- `A9`: current branch readiness must be re-established under the superseding spec because 2026-04-30 local evidence found WEB lint/build failures and API golden-path/AI truthfulness failures.
- `A10`: release posture must be selected and disclosed as AI-enabled alpha or manual-continuity alpha; ambiguous AI availability is not acceptable.
- `A11`: manual continuity and human-in-the-loop review must be verified as permanent product requirements, not alpha-only fallback behavior.

## Blocking Condition

No design-partner release decision is valid until:
- staging WEB and API URLs are recorded
- staging smoke evidence is captured
- local API and WEB gates are green under the superseding spec
- AI feature availability or unavailability is disclosed truthfully
- manual continuity is proven end-to-end
- release owners sign off in the current release checklist
