# CRM Hardening Summary (March 2026)

## Scope

Hardening work focused on the CRM lead lifecycle because it had the highest-impact functional and safety gaps:

- write-path methods accepted parameters but did not apply changes,
- conversion used weak synthetic identifiers (`uniqid`),
- delete/restore did not actually affect persistence state,
- lead scoring could divide by zero with invalid custom weight sets.

## Implemented Improvements

### 1) Lead lifecycle write-path correctness (`LeadManager`)

- Implemented real state mutation for:
  - `update()`
  - `updateStatus()`
  - `updateSource()`
  - `assignScore()`
  - `convertToOpportunity()`
  - `disqualify()`
- Implemented operational delete/restore behavior:
  - `delete()` now removes active lead and stores it in an internal deleted store.
  - `restore()` now restores from deleted storage and throws when no deleted lead exists.
- Added secure identifier generation using `random_bytes()` (with domain exception fallback) for lead and opportunity IDs.
- Returned lead entities now expose private state only through interface getters, preventing external mutation of status/tenant/source fields.
- Enforced tenant scope per in-memory `LeadManager` instance (single-tenant binding) to prevent cross-tenant state mixing.

### 2) Lead scoring safety (`LeadScoringEngine`)

- Added a guard for zero/invalid total weights in weighted score calculation.
- Added weight/input sanitization and score clamping (`0..100`) to avoid malformed context causing invalid score objects.
- Boolean context flags are now parsed safely (string/bool payloads), and unknown custom weight keys are excluded from denominator calculations.
- Engine now logs a warning and returns a safe score (`0`) instead of risking division-by-zero.

## Test Coverage Added

- New `LeadManagerTest` covering:
  - update behavior,
  - transition enforcement,
  - score assignment + query behavior,
  - conversion metadata,
  - non-qualified conversion rejection,
  - delete/restore flow.
- Extended `LeadScoringEngineTest` with zero-weight safeguard test.
- Added tests for entity immutability and negative input/weight sanitization.
- Added tests for unknown weight-key handling and strict boolean context parsing.

## Outcome

The CRM package now enforces meaningful state transitions and persistence behavior for leads, removes weak identifier generation in lead conversion flow, and protects scoring logic from runtime arithmetic failure.
