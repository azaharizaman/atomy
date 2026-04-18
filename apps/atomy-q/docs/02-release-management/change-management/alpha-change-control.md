# Alpha Change Control

This is the canonical change-management policy for Atomy-Q alpha remediation and stabilization work.

## Allowed Scope

Allowed without scope-expansion approval:
- error remediation in existing alpha-supported flows
- UI and UX refinements in existing alpha-supported screens
- removal of remaining mock fallback behavior in live mode
- test hardening and documentation updates tied to those changes

Not allowed without explicit approval:
- new user-facing modules outside current alpha scope
- broad refactors not required for a verified alpha defect
- enabling deferred modules or broadening the alpha promise

## Change Classes

| Class | Definition | Minimum gates |
|---|---|---|
| `C1` | cosmetic UI/copy/layout only | targeted tests plus checklist entry |
| `C2` | behavior change inside an existing alpha flow | targeted tests, relevant live-mode tests, `npm run build`, checklist entry |
| `C3` | auth, tenant scoping, API contract, RFQ/quote/comparison/award behavior, staging/runtime, or cross-cutting adapter changes | WEB lint/build/unit, API alpha suite, targeted E2E smoke, checklist entry, explicit reviewer sign-off |

If classification is unclear, treat the change as `C3`.

## Mandatory Path

1. Open a change entry in the current release checklist.
2. Map the change to affected blockers and release tasks.
3. Confirm no scope expansion, or document the requested expansion.
4. Run the required gates for the class.
5. Record non-regression evidence.
6. Merge only after the required sign-offs are present.

## Hard Stops

Do not merge if:
- live mode silently falls back to seed/mock behavior on alpha pages
- a previously closed blocker regresses without an accepted waiver
- tenant-scoped 404 semantics are weakened
- API/WEB contract drift is introduced without regeneration and verification
- a deferred surface becomes reachable in alpha mode without a decision
